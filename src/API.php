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

use \PerspectiveSimulator\Libs\Util;

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
    public static function getAPIPath(string $project)
    {
        return \PerspectiveSimulator\Libs\FileSystem::getProjectDir($project).'/API';

    }//end getAPIPath()


    /**
     * Gets the file path for the API function.
     *
     * @param string $project The namespace of the project we want the action from.
     * @param string $action  The action we want to perform.
     *
     * @return string
     * @throws \Exception When the API operation doesn't exist.
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
     * Import YAML spec.
     *
     * @param string $project The project we are using.
     *
     * @return boolean
     * @throws \Exception When unable to get API Paths.
     */
    public static function installAPI(string $project)
    {
        $apiFile = self::getAPIPath($project).'/api.yaml';
        if (file_exists($apiFile) === false) {
            // No api file so nothing to do here.
            return true;
        }

        $yaml = file_get_contents($apiFile);
        ini_set('yaml.decode_php', 0);
        $parsed = yaml_parse($yaml);
        if ($parsed === false || empty($parsed['paths']) === true) {
            throw new \Exception('Failed to parse API specficiation');
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
                        foreach ($params as &$param) {
                            $param['__php_name__'] = self::getParamCamelCase($param['name']);
                            $parameters[]          = $param;
                        }
                    }

                    $apis[$httpMethod][] = [
                        'path'        => $path,
                        'description' => $operationSettings['description'],
                        'http_method' => $httpMethod,
                        'operationid' => $operationSettings['operationId'],
                        'parameters'  => $parameters,
                    ];
                }//end if
            }//end foreach
        }//end foreach

        // Bake the simulator router and API functions.
        self::bakeRouter($apis, $project);
        self::bakeAPIFunctions($apis, $project);

        return true;

    }//end installAPI()


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
        $router  = Util::printCode(0, '<?php');
        $router .= Util::printCode(0, 'namespace '.$project.';');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, 'class APIRouter {');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(1, 'public static function process($path, $httpMethod, $queryParams)');
        $router .= Util::printCode(1, '{');
        $router .= Util::printCode(2, '$operationid = null;');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(2, 'switch ($httpMethod) {');

        foreach ($apis as $method => $paths) {
            $case = 'case \''.$method.'\':';
            if ($method === 'get') {
                $case .= ' case \'head\':';
            }

            $router .= Util::printCode(3, $case);

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

                    $router .= Util::printCode(4, '$matches = [];');
                    $router .= Util::printCode(4, 'if (preg_match(\'~^'.$api['path'].'$~\', $path, $matches) === 1) {');
                    foreach ($pathParams as $param) {
                        $matchIndex                = ($matchIndexes[$param['name']] + 1);
                        $arguments[$param['name']] = '$matches['.$matchIndex.']';
                    }
                } else {
                    $router .= Util::printCode(4, 'if ($path === \''.$api['path'].'\') {');
                }//end if

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

                $router .= Util::printCode(5, '$operationid  = \''.$api['operationid'].'\';');
                $router .= Util::printCode(5, '$arguments    = [');
                foreach ($arguments as $argIndex => $argValue) {
                    $router .= Util::printCode(6, '\''.$argIndex.'\' => '.$argValue.',');
                }

                $router .= Util::printCode(5, '];');
                $router .= Util::printCode(5, 'break;');
                $router .= Util::printCode(4, '}');
            }//end foreach
        }//end foreach

        $router .= Util::printCode(2, '}//end switch');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(2, 'if ($operationid !== null) {');
        $router .= Util::printCode(3, '$requestBody = file_get_contents(\'php://input\');');
        $router .= Util::printCode(
            3,
            '$contentType = ($_SERVER[\'HTTP_CONTENT_TYPE\'] ?? $_SERVER[\'CONTENT_TYPE\'] ?? \'\');'
        );
        $router .= Util::printCode(3, 'if (strpos($contentType, \'application/json\') !== false) {');
        $router .= Util::printCode(4, '$requestBody = json_decode($requestBody, true);');
        $router .= Util::printCode(3, '}');
        $router .= Util::printCode(3, '$arguments[] = $requestBody;');
        $router .= Util::printCode(3, '$api = new API;');
        $router .= Util::printCode(3, '$output = call_user_func_array([$api, $operationid], $arguments);');
        $router .= Util::printCode(3, 'header(\'Content-Type: application/json\');');
        $router .= Util::printCode(3, 'echo json_encode($output);');
        $router .= Util::printCode(2, '} else {');
        $router .= Util::printCode(3, 'header(\'HTTP/1.1 404 Not Found\');');
        $router .= Util::printCode(3, 'exit();');
        $router .= Util::printCode(2, '}');
        $router .= Util::printCode(1, '}//end process');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, '}//end class');

        $routerFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$project.'/APIRouter.php';
        file_put_contents($routerFile, $router);

    }//end bakeRouter()


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
        $function  = Util::printCode(0, '<?php');
        $function .= Util::printCode(0, 'namespace '.$project.';');
        $function .= Util::printCode(0, '');
        $function .= Util::printCode(0, 'class API');
        $function .= Util::printCode(0, '{');
        $function .= Util::printCode(0, '');
        $function .= Util::printCode(0, '');

        foreach ($apis as $method => $paths) {
            foreach ($paths as $id => $api) {
                $functionSignature = 'public function '.$api['operationid'].'(';
                $arguments         = [];
                $requiredArgs      = [];
                $optionalArgs      = [];
                $allParams         = [];
                foreach ($api['parameters'] as $param) {
                    $allParams[$param['in']][$param['name']] = $param;
                }

                try {
                    // Check function file exists.
                    self::getAPIFunction($project, $api['operationid']);
                } catch (\Exception $e) {
                    // Add new operation file.
                    self::generateAPIFunction($project, $api, $allParams);
                }

                $pathParams = ($allParams['path'] ?? []);
                if (empty($pathParams) === false) {
                    foreach ($pathParams as $param) {
                        if ($param['required'] === true) {
                            $requiredArgs[] = '$'.$param['__php_name__'];
                        } else {
                            $default = ($param['schema']['default'] ?? null);
                            if (is_scalar($default) === true) {
                                $optionalArgs[] = '$'.$param['__php_name__'].' = '.var_export($default, true);
                            } else {
                                $optionalArgs[] = '$'.$param['__php_name__'].' = null';
                            }
                        }
                    }
                }

                $queryParams = ($allParams['query'] ?? []);
                foreach ($queryParams as $param) {
                    if ($param['required'] === true) {
                        $requiredArgs[] = '$'.$param['__php_name__'];
                    } else {
                        $default = ($param['schema']['default'] ?? null);
                        if (is_scalar($default) === true) {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = '.var_export($default, true);
                        } else {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = null';
                        }
                    }
                }

                $headerParams = ($allParams['header'] ?? []);
                foreach ($headerParams as $param) {
                    if ($param['required'] === true) {
                        $requiredArgs[] = '$'.$param['__php_name__'];
                    } else {
                        $default = ($param['schema']['default'] ?? null);
                        if (is_scalar($default) === true) {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = '.var_export($default, true);
                        } else {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = null';
                        }
                    }
                }

                $cookieParams = ($allParams['cookie'] ?? []);
                foreach ($cookieParams as $param) {
                    if ($param['required'] === true) {
                        $requiredArgs[] = '$'.$param['__php_name__'];
                    } else {
                        $default = ($param['schema']['default'] ?? null);
                        if (is_scalar($default) === true) {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = '.var_export($default, true);
                        } else {
                            $optionalArgs[] = '$'.$param['__php_name__'].' = null';
                        }
                    }
                }

                $arguments = array_merge($requiredArgs, $optionalArgs);
                if ($api['http_method'] !== 'get') {
                    $arguments[] = '$requestBody = null';
                }

                $functionSignature .= implode(', ', $arguments);
                $functionSignature .= ')';

                $function .= Util::printCode(1, $functionSignature);
                $function .= Util::printCode(1, '{');
                $function .= Util::printCode(
                    2,
                    '$content = \PerspectiveSimulator\API::getAPIFunction(__NAMESPACE__, \''.$api['operationid'].'\');'
                );
                $function .= Util::printCode(2, 'return eval($content);');
                $function .= Util::printCode(1, '}');
                $function .= Util::printCode(0, '');
                $function .= Util::printCode(0, '');
            }//end foreach
        }//end foreach

        $function .= Util::printCode(0, '}//end class');

        $functionFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$project.'/API.php';
        file_put_contents($functionFile, $function);

    }//end bakeAPIFunctions()


    /**
     * Generates the boilerplate API function file.
     *
     * @param string $project   The project we are currently installing.
     * @param array  $api       The api details for the new opperation.
     * @param array  $allParams All the API paramters, in order.
     *
     * @return void
     */
    private static function generateAPIFunction(string $project, array $api, array $allParams)
    {
        $functionContent  = Util::printCode(0, '<?php');
        $functionContent .= Util::printCode(0, '/**');
        $functionContent .= Util::printCode(0, ' * API stub for the '.$api['operationid'].'() operation.');
        $functionContent .= Util::printCode(0, ' *');
        $functionContent .= Util::printCode(0, ' * '.$api['description']);
        $functionContent .= Util::printCode(0, ' *');

        foreach ($allParams as $type => $params) {
            foreach ($params as $param) {
                $paramCommement   = ' * @param '.$param['schema']['type'].' $'.$param['name'].' '.$param['description'];
                $functionContent .= Util::printCode(0, $paramCommement);
            }
        }

        if ($api['http_method'] !== 'get') {
            $functionContent .= Util::printCode(0, ' * @param array $requestBody');
        }

        $functionContent .= Util::printCode(0, ' */');
        $functionContent .= Util::printCode(0, '');

        $file = self::getAPIPath($project).'/Operations/'.$api['operationid'].'.php';
        file_put_contents($file, $functionContent);

    }//end generateAPIFunction()


    /**
     * Camel case a parameter name.
     *
     * @param string $paramName The parameter name.
     *
     * @return string
     */
    private static function getParamCamelCase(string $paramName)
    {
        $paramName = str_replace(' ', '_', $paramName);
        $paramName = str_replace('-', '_', $paramName);
        $parts     = explode('_', $paramName);
        if (count($parts) === 1) {
            if (strtoupper($parts[0]) === $parts[0]) {
                $parts[0] = strtolower($parts[0]);
            } else {
                $parts[0] = lcfirst($parts[0]);
            }
        } else {
            foreach ($parts as $idx => $part) {
                if ($idx === 0) {
                    $parts[$idx] = strtolower($part);
                } else {
                    $parts[$idx] = ucfirst(strtolower($part));
                }
            }
        }

        $paramName = implode('', $parts);
        return $paramName;

    }//end getParamCamelCase()


}//end class
