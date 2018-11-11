<?php
/**
 * API class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;


/**
 * API class
 */
class API
{

    /**
     * Supported http methods.
     *
     * @var array
     */
    const HTTP_METHODS = [
        'get',
        'post',
        'delete',
        'patch',
        'put',
    ];


    /**
     * Gets the API file path.
     *
     * @param string $project The namespace of the project.
     *
     * @return string
     */
    private static function getAPIPath(string $project)
    {
        return dirname(__DIR__, 4).'/projects/'.$project.'/API';

    }


    /**
     * Gets the file path for the API function.
     *
     * @param string $project The namespace of the project we want the action from.
     * @param string $action  The action we want to perform.
     *
     * @return string
     */
    public static function getAPIFunction(string $project, string $action)
    {
        $file = self::getAPIPath($project).'/Operations/'.$action.'.php';
        if (is_file($file) === false) {
            throw new \Exception('API operation "'.$action.'" does not exist');
        }

        $content = file_get_contents($file);
        $content = str_replace('<?php', '', $content);
        return $content;

    }//end getAPIFunction()


    /**
     * Bake all API Function calls and router.
     *
     * @return void
     */
    public static function installAPI()
    {
        $projectPath = dirname(__DIR__, 4).'/Projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                self::importYAMLSpec($project);
            }
        }

        return true;

    }//end installAPI()



    /**
     * Import YAML spec.
     *
     * @param string  $project The project we are using.
     *
     * @return void
     */
    private static function importYAMLSpec(string $project)
    {
        $yaml = file_get_contents(self::getAPIPath($project).'/api.yaml');
        ini_set('yaml.decode_php', 0);
        $parsed = yaml_parse($yaml);
        if ($parsed === false || empty($parsed['paths']) === true) {
            throw new \Exception(_('Failed to parse API specficiation'));
        }

        $apis = [
            'get'    => [],
            'post'   => [],
            'delete' => [],
            'patch'  => [],
            'put'    => [],
        ];
        foreach ($parsed['paths'] as $path => $pathSettings) {
            foreach ($pathSettings as $httpMethod => $operationSettings) {
                $httpMethod = strtolower($httpMethod);
                if (in_array($httpMethod, self::HTTP_METHODS) === true) {
                    $parameters = [];
                    $allParams  = [];
                    foreach (($pathSettings['parameters'] ?? []) as $param) {
                        $allParams[$param['in']][$param['name']] = $param;
                    }

                    foreach (($operationSettings['parameters'] ?? []) as $param) {
                        $allParams[$param['in']][$param['name']] = $param;
                    }

                    // Respect order of parameters.
                    foreach ($allParams as $params) {
                        foreach ($params as $param) {
                            $parameters[] = $param;
                        }
                    }

                    $apis[$httpMethod][] = [
                        'path'        => $path,
                        'http_method' => $httpMethod,
                        'operationid' => $operationSettings['operationId'],
                        'parameters'  => $parameters,
                    ];
                }//end if
            }
        }

        // Bake the simulator router and API functions.
        self::bakeRouter($apis, $project);
        self::bakeAPIFunctions($apis, $project);

        return true;

    }//end importYAMLSpec()


    /**
     * Fromats code lines.
     *
     * @param integer $level The indentation level.
     * @param string  $line  The line of code.
     *
     * @return string
     */
    private static function printCode($level, $line)
    {
        $indent = function($lvl) {
            return str_repeat(' ', ($lvl * 4));
        };

        return $indent($level).$line."\n";

    }//end printCode()


    /**
     * Bakes router class.
     *
     * @param array  $apis    The api paths to bake.
     * @param string $project The project the router belongs to.
     *
     * @return void
     */
    private static function bakeRouter(array $apis, string $project)
    {
        $router  = self::printCode(0, '<?php');
        $router .= self::printCode(0, 'namespace '.$project);
        $router .= self::printCode(0, '');
        $router .= self::printCode(0, 'class APIRouter {');
        $router .= self::printCode(0, '');
        $router .= self::printCode(1, 'public static function process($path, $httpMethod, $queryParams)');
        $router .= self::printCode(1, '{');
        $router .= self::printCode(2, '$operationid = null;');
        $router .= self::printCode(0, '');
        $router .= self::printCode(2, 'switch ($httpMethod) {');

        foreach ($apis as $method => $paths) {
            $case = 'case \''.$method.'\':';
            if ($method === 'get') {
                $case .= ' case \'head\':';
            }
            $router .= self::printCode(3, $case);

            foreach ($paths as $id => $api) {
                $api['path'] = ltrim($api['path'], '/');

                $arguments = [];
                $allParams = [];
                foreach ($api['parameters'] as $param) {
                    $arguments[$param['name']]               = var_export(null, true);
                    $allParams[$param['in']][$param['name']] = $param;
                }

                $pathParams = ($allParams['path'] ?? []);
                if (empty($pathParams) === false) {
                    $matchIndexes = [];
                    preg_match_all(
                        '/\{('.implode('|', array_keys($pathParams)).')\}/',
                        $api['path'],
                        $matchIndexes
                    );

                    $matchIndexes = array_flip($matchIndexes[1]);
                    $api['path']  = preg_quote($api['path'], '~');

                    foreach ($pathParams as $param) {
                        $type = ($param['schema']['type'] ?? null);
                        if ($type === 'number') {
                            $replace = '([-+]?[0-9\.]+)';
                        } else {
                            $replace = '([^/]+)';
                        }

                        $api['path'] = str_replace('\{'.$param['name'].'\}', $replace, $api['path']);
                    }

                    $router .= self::printCode(4, '$matches = [];');
                    $router .= self::printCode(4, 'if (preg_match(\'~^'.$api['path'].'$~\', $path, $matches) === 1) {');
                    foreach ($pathParams as $param) {
                        $matchIndex                = ($matchIndexes[$param['name']] + 1);
                        $arguments[$param['name']] = '$matches['.$matchIndex.']';
                    }
                } else {
                    $router .= self::printCode(4, 'if ($path === \''.$api['path'].'\') {');
                }

                $queryParams = ($allParams['query'] ?? []);
                foreach ($queryParams as $param) {
                    $arguments[$param['name']] = '$queryParams[\''.$param['name'].'\'] ?? null';
                }

                $headerParams = ($allParams['header'] ?? []);
                foreach ($headerParams as $param) {
                    $serverIndex = str_replace('-', '_', $param['name']);
                    $serverIndex = str_replace(' ', '_', $serverIndex);
                    $serverIndex = 'HTTP_'.strtoupper($serverIndex);

                    $arguments[$param['name']] = '$_SERVER[\''.$serverIndex.'\'] ?? null';
                }

                $cookieParams = ($allParams['cookie'] ?? []);
                foreach ($cookieParams as $param) {
                    $arguments[$param['name']] = '$_COOKIE[\''.$param['name'].'\'] ?? null';
                }

                $router .= self::printCode(5, '$operationid  = \''.$api['operationid'].'\';');
                $router .= self::printCode(5, '$arguments    = [');
                foreach ($arguments as $argIndex => $argValue) {
                    $router .= self::printCode(6, '\''.$argIndex.'\' => '.$argValue.',');
                }

                $router .= self::printCode(5, '];');
                $router .= self::printCode(5, 'break;');
                $router .= self::printCode(4, '}');
            }// end foreach
        }//end foreach

        $router .= self::printCode(2, '}//end switch');
        $router .= self::printCode(0, '');
        $router .= self::printCode(2, 'if ($operationid !== null) {');
        $router .= self::printCode(3, '$requestBody = file_get_contents(\'php://input\');');
        $router .= self::printCode(3, '$contentType = ($_SERVER[\'HTTP_CONTENT_TYPE\'] ?? $_SERVER[\'CONTENT_TYPE\'] ?? \'\');');
        $router .= self::printCode(3, 'if (strpos($contentType, \'application/json\') !== false) {');
        $router .= self::printCode(4, '$requestBody = json_decode($requestBody, true);');
        $router .= self::printCode(3, '}');
        $router .= self::printCode(3, '$arguments[] = $requestBody;');
        $router .= self::printCode(3, '$api = new API;');
        $router .= self::printCode(3, '$output = call_user_func_array([$api, $operationid], $arguments);');
        $router .= self::printCode(3, 'header(\'Content-Type: application/json\');');
        $router .= self::printCode(3, 'echo json_encode($output);');
        $router .= self::printCode(2, '} else {');
        $router .= self::printCode(3, 'header(\'HTTP/1.1 404 Not Found\');');
        $router .= self::printCode(3, 'exit();');
        $router .= self::printCode(2, '}');
        $router .= self::printCode(1, '}//end process');
        $router .= self::printCode(0, '');
        $router .= self::printCode(0, '');
        $router .= self::printCode(0, '}//end class');

        $routerFile = Bootstrap::getSimulatorDir().'/'.$project.'/APIRouter.php';
        file_put_contents($routerFile, $router);

    }//end printCode()


    /**
     * Bakes router class.
     *
     * @param array  $apis    The api paths to bake.
     * @param string $project The project the functions belong to.
     *
     * @return void
     */
    private static function bakeAPIFunctions(array $apis, string $project)
    {
        $function  = self::printCode(0, '<?php');
        $function .= self::printCode(0, 'namespace '.$project);
        $function .= self::printCode(0, '');
        $function .= self::printCode(0, 'class API');
        $function .= self::printCode(0, '{');
        $function .= self::printCode(0, '');
        $function .= self::printCode(0, '');

        foreach ($apis as $method => $paths) {
            foreach ($paths as $id => $api) {
                $functionSignature = 'public function '.$api['operationid'].'(';
                $arguments = [];
                $allParams = [];
                foreach ($api['parameters'] as $param) {
                    $allParams[$param['in']][$param['name']] = $param;
                }

                $pathParams = ($allParams['path'] ?? []);
                if (empty($pathParams) === false) {
                    foreach ($pathParams as $param) {
                        $arguments[] = '$'.$param['name'];
                    }
                }

                $queryParams = ($allParams['query'] ?? []);
                foreach ($queryParams as $param) {
                    $arguments[] = '$'.$param['name'];
                }

                $headerParams = ($allParams['header'] ?? []);
                foreach ($headerParams as $param) {
                    $arguments[] = '$'.$param['name'];
                }

                $cookieParams = ($allParams['cookie'] ?? []);
                foreach ($cookieParams as $param) {
                    $arguments[] = '$'.$param['name'];
                }

                if ($api['http_method'] !== 'get') {
                    $arguments[] = '$requestBod=null';
                }

                $functionSignature .= implode(',', $arguments);
                $functionSignature .= ')';

                $function .= self::printCode(1, $functionSignature);
                $function .= self::printCode(1, '{');
                $function .= self::printCode(2, '$content = \PerspectiveSimulator\API::getAPIFunction(__NAMESPACE__, \''.$api['operationid'].'\');');
                $function .= self::printCode(2, 'return eval($content);');
                $function .= self::printCode(1, '}');
                $function .= self::printCode(0, '');
                $function .= self::printCode(0, '');
            }//end foreach
        }//end foreach

        $function .= self::printCode(0, '}//end class');

        $functionFile = Bootstrap::getSimulatorDir().'/'.$project.'/API.php';
        file_put_contents($functionFile, $function);

    }//end printCode()


}//end class
