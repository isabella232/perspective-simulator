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

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs\Util;

/**
 * API class
 */
class API
{

    /**
     * Base router code.
     *
     * @var string
     */
    const router = '$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
__ROUTES__
        });

        if (false !== $pos = strpos($path, \'?\')) {
            $path = substr($path, 0, $pos);
        }

        $path = rawurldecode($path);
        $routeInfo = $dispatcher->dispatch($httpMethod, $path);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // 404 Not Found.
                header(\'HTTP/1.1 404 Not Found\');
                exit();
            break;

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // 405 Method Not Allowed.
                header(\'HTTP/1.1 405 Method Not Allowed\');
                exit();
            break;

            case \FastRoute\Dispatcher::FOUND:
                $handler     = $routeInfo[1];
                $vars        = $routeInfo[2];
                $requestBody = file_get_contents(\'php://input\');
                $contentType = ($_SERVER[\'HTTP_CONTENT_TYPE\'] ?? $_SERVER[\'CONTENT_TYPE\'] ?? \'\');

                if (strpos($contentType, \'application/json\') !== false) {
                    $requestBody = json_decode($requestBody, true);
                }

                __ROUTE_ARGUMENTS__

                if (isset($arguments[$handler]) === true) {
                    $vars = array_merge($vars, $arguments[$handler]);
                }

                $vars[] = $requestBody;
                $api    = new API;
                $output = call_user_func_array([$api, $handler], $vars);

                header(\'Content-Type: application/json\');
                echo json_encode($output);
            break;
        }//end switch';

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
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $project = str_replace('\\', '/', $project);
            $dir     = substr(\PerspectiveSimulator\Libs\FileSystem::getProjectDir($GLOBALS['project']), 0, -4);

            return $dir.'/vendor/'.$project.'/src/API';
        } else {
            return \PerspectiveSimulator\Libs\FileSystem::getProjectDir($project).'/API';
        }

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
     * Parses the YAML API file and returns its paths as an array.
     *
     * @param string $path File path of the yaml file.
     *
     * @return array
     * @throws \Exception When error occurs or there is no file to parse.
     */
    private static function parseYaml(string $path)
    {
        if (file_exists($path) === false) {
            throw new \Exception(sprintf('YAML file doesn\'t exist at the location "%s".', $path));
        }

        ini_set('yaml.decode_php', 0);
        $yaml   = file_get_contents($path);
        $parsed = \Symfony\Component\Yaml\Yaml::parse($yaml);
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

        return $apis;

    }//end parseYaml()


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
        $showPrompt = false;
        if (php_sapi_name() === 'cli') {
            $showPrompt = true;
        }

        $apiFile = self::getAPIPath($project).'/api.yaml';
        if (file_exists($apiFile) === false) {
            // No api to install so just return now.
            return true;
        }

        $apis = self::parseYaml($apiFile);

        $prefix            = Bootstrap::generatePrefix($project);
        $currentAPIs       = [];
        $simulatorYamlFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/'.$prefix.'-api.yaml';
        if (file_exists($simulatorYamlFile) === true) {
            $currentAPIs = self::parseYaml($simulatorYamlFile);
        }
        $newFile = file_get_contents($apiFile);
        file_put_contents($simulatorYamlFile, $newFile);

        $currentByPathOperation = [];
        $currentByOperationid   = [];
        if (empty($currentAPIs) === false) {
            foreach ($currentAPIs as $method => $paths) {
                foreach ($paths as $id => $current) {
                    $currentByOperationid[$current['operationid']] = $current;
                    $currentByPathOperation[$current['path']][$current['http_method']] = $current;
                }
            }
        }

        $updatedIDs    = [];
        $newPaths      = [];
        $renamedPaths  = [];
        $deletedPaths  = [];
        foreach ($apis as $method => $paths) {
            foreach ($paths as $id => $api) {
                $path       = $api['path'];
                $current    = null;
                if (isset($currentByPathOperation[$path][$api['http_method']]) === true) {
                    $current = $currentByPathOperation[$path][$api['http_method']];
                } else if (isset($currentByOperationid[$api['operationid']]) === true) {
                    $current = $currentByOperationid[$api['operationid']];
                }

                if ($current !== null) {
                    $updatedIDs[] = $current['operationid'];
                    if ($current['path'] !== $api['path']
                        || $current['http_method'] !== $api['http_method']
                        || $current['operationid'] !== $api['operationid']
                    ) {
                        $renamedPaths[] = [
                            'oldOperationid' => $current['operationid'],
                            'newOperationid' => $api['operationid'],
                        ];
                    }
                } else {
                    $newPaths[] = [
                        'http_method' => $api['http_method'],
                        'path'        => $api['path'],
                        'operationid' => $api['operationid'],
                    ];
                }//end if
            }//end foreach
        }//end foreach

        $currentIDs = array_keys($currentByOperationid);
        $deletedIDs = array_diff($currentIDs, $updatedIDs);
        foreach ($deletedIDs as $deleteID) {
            $deletedPaths[] = $deleteID;
        }

        if (empty($renamedPaths) === false) {
            if ($showPrompt === true) {
                $renamedMessage = "The following API operations will be renamed:\n";
                foreach ($renamedPaths as $id => $path) {
                    $count = ($id + 1);
                    if (strtolower($path['oldOperationid']) !== strtolower($path['newOperationid'])) {
                        $renamedMessage .= $count.'. '.$path['oldOperationid'].' => '.$path['newOperationid'];
                    }
                }

                $confirm = \PerspectiveSimulator\CLI\Prompt::confirm($renamedMessage."\n");
                if ($confirm === false) {
                    exit();
                }
            }

            foreach ($renamedPaths as $path) {
                if ($path['oldOperationid'] !== $path['newOperationid']) {
                    $source = self::getAPIPath($project).'/Operations/'.$path['oldOperationid'].'.php';
                    $dest   = self::getAPIPath($project).'/Operations/'.$path['newOperationid'].'.php';
                    \PerspectiveSimulator\Libs\FileSystem::move($source, $dest);
                }
            }
        }

        if (empty($deletedPaths) === false) {
            if ($showPrompt === true) {
                $deleteMessage = "The following API operations will be deleted:\n";
                foreach ($deletedPaths as $op) {
                    $count = ($id + 1);
                    $deleteMessage .= $count.'. '.$op;
                }

                $confirm = \PerspectiveSimulator\CLI\Prompt::confirm($deleteMessage."\n");
                if ($confirm === false) {
                    exit();
                }
            }

            foreach ($deletedPaths as $opId) {
                $file = self::getAPIPath($project).'/Operations/'.$opId.'.php';
                \PerspectiveSimulator\Libs\FileSystem::delete($file);
            }
        }

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
        $router .= Util::printCode(0, 'namespace '.str_replace('/', '\\', $project).';');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, 'class APIRouter {');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(1, 'public static function process($path, $httpMethod, $queryParams)');
        $router .= Util::printCode(1, '{');

        $arguments = [];
        $routeCode = '';
        $argCode   = Util::printCode(0, '$arguments = [');
        foreach ($apis as $method => $paths) {
            $case = '\''.$method.'\'';
            if ($method === 'get') {
                $case .= ', \'head\'';
            }

            foreach ($paths as $id => $api) {
                $api['path'] = ltrim($api['path'], '/');

                $routeCode .= Util::printCode(3, '$r->addRoute(['.$case.'], \''.$api['path'].'\', \''.$api['operationid'].'\');');

                $arguments[$api['operationid']] = [];
                $allParams = [];
                foreach ($api['parameters'] as $param) {
                    if ($param['in'] !== 'path') {
                        $arguments[$api['operationid']][$param['name']] = var_export(null, true);
                        $allParams[$param['in']][$param['name']]        = $param;
                    }
                }

                $queryParams = ($allParams['query'] ?? []);
                foreach ($queryParams as $param) {
                    $arguments[$api['operationid']][$param['name']] = '$queryParams[\''.$param['name'].'\'] ?? null';
                }

                $headerParams = ($allParams['header'] ?? []);
                foreach ($headerParams as $param) {
                    $serverIndex = str_replace('-', '_', $param['name']);
                    $serverIndex = str_replace(' ', '_', $serverIndex);
                    $serverIndex = 'HTTP_'.strtoupper($serverIndex);

                    $arguments[$api['operationid']][$param['name']] = '$_SERVER[\''.$serverIndex.'\'] ?? null';
                }

                $cookieParams = ($allParams['cookie'] ?? []);
                foreach ($cookieParams as $param) {
                    $arguments[$api['operationid']][$param['name']] = '$_COOKIE[\''.$param['name'].'\'] ?? null';
                }

                $argCode .= Util::printCode(5, '\''.$api['operationid'].'\' => [');
                foreach ($arguments[$api['operationid']] as $argIndex => $argValue) {
                    $argCode .= Util::printCode(6, '\''.$argIndex.'\' => '.$argValue.',');
                }
                $argCode .= Util::printCode(5, '],');
            }//end foreach
        }//end foreach

        $argCode .= Util::printCode(4, '];');

        $routesCode = str_replace('__ROUTE_ARGUMENTS__', $argCode, self::router);
        $routesCode = str_replace('__ROUTES__', $routeCode, $routesCode);

        $router .= Util::printCode(2, $routesCode);
        $router .= Util::printCode(1, '}//end process');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, '');
        $router .= Util::printCode(0, '}//end class');

        $prefix = Bootstrap::generatePrefix($project);
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $routerFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/'.$prefix.'-apirouter.php';
        } else {
            $routerFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$project.'/'.$prefix.'-apirouter.php';
        }

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
        $function .= Util::printCode(0, 'namespace '.str_replace('/', '\\', $project).';');
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

        $prefix = Bootstrap::generatePrefix($project);
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $functionFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/'.$prefix.'-api.php';
        } else {
            $functionFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$project.'/'.$prefix.'-api.php';
        }

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
