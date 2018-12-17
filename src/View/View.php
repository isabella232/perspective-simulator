<?php
/**
 * View class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\View;

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs\Util;
use \PerspectiveSimulator\Libs\FileSystem;

/**
 * View class
 */
class View
{

    private static $twigLoader = null;


    /**
     * Gets the twig loader
     *
     * @return object
     */
    private static function getTwigLoader()
    {
        if (self::$twigLoader === null) {
            $path = FileSystem::getProjectDir().'/web/views';
            self::$twigLoader = new TwigLoader($path);
        }

        return self::$twigLoader;

    }//end getTwigLoader()


    /**
     * Renders the twig view.
     *
     * @param string $viewid The view we are printing.
     *
     * @return mixed
     */
    public static function render(string $viewid)
    {
        $twig = new \Twig_Environment(
            self::getTwigLoader(),
            [
                'debug'       => true,
                'auto_reload' => true,
                'cache'       => false,
            ]
        );

        $data   = self::gatherRenderingData($viewid);
        $result = $twig->render($viewid, $data);
        echo $result;

    }//end render()


    /**
     * Gets the rendering data.
     *
     * @param string $viewid The view we are getting the data for.
     *
     * @return array
     */
    public static function gatherRenderingData(string $viewid)
    {
        $data = [];
        foreach (self::getTemplateTree($viewid, true) as $value) {
            switch ($value['type']) {
                case 'extends':
                case 'include':
                case 'embed':
                    $data = array_merge(
                        self::gatherRenderingData($value['code']),
                        $data
                    );
                break;

                default:
                    $class = $GLOBALS['project'].'\\Web\\Views\\'.str_replace('/', '\\', $viewid);
                    if (class_exists($class) === true) {
                        $viewClass = new $class();
                        $data = array_merge(
                            $viewClass->getViewData(),
                            $data
                        );
                    }
                break;
            }//end switch
        }//end foreach

        return $data;

    }//end gatherRenderingData()


    /**
     * Gets the Twig Template Tree
     *
     * @param string     $viewid         The path of the view.
     * @param boolean    $returnIterator Flag to return the iterator
     * @param TwigLoader $loader         Twig loader.
     *
     * @return object
     */
    public static function getTemplateTree(
        string $viewid,
        bool $returnIterator=false,
        TwigLoader $loader=null
    ) {
        if ($loader === null) {
            $loader = self::getTwigLoader();
        }

        $twig = new \Twig_Environment(
            $loader,
            [
                'debug'       => true,
                'auto_reload' => true,
                'cache'       => false,
            ]
        );

        $stream = $twig->tokenize($loader->getSourceContext($viewid));
        $tree   = self::parseTemplate($stream);
        if ($returnIterator === false) {
            return $tree;
        }

        return new \RecursiveIteratorIterator(
            new TreeIterator($tree),
            \RecursiveIteratorIterator::SELF_FIRST
        );

    }//end getTemplateTree()


    /**
     * Parse the twig template.
     *
     * @param mixed   &$stream
     * @param integer $depth
     *
     * @return array
     */
    public static function parseTemplate(&$stream, $depth=0)
    {
        $nodes = [];

        $blockOpenCount = 0;
        $embedOpenCount = 0;
        while (!$stream->isEOF()) {
            if ($stream->getCurrent()->getType() !== \Twig_Token::BLOCK_START_TYPE) {
                $stream->next();
                continue;
            }

            $stream->next();
            if ($stream->getCurrent()->getType() !== \Twig_Token::NAME_TYPE) {
                throw new ChannelException(
                    'A block must start with a tag name.',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            }

            $node      = ['depth' => $depth];
            $blockName = $stream->getCurrent()->getValue();
            switch ($blockName) {
                case 'block':
                    $stream->next();

                    $node['type'] = $blockName;
                    if ($stream->getCurrent()->getType() === \Twig_Token::NAME_TYPE) {
                        $node['title'] = $stream->getCurrent()->getValue();
                    } {
                        // EXCEPTION.
                    }

                    $stream->next();
                    if ($stream->getCurrent()->getType() === \Twig_Token::BLOCK_END_TYPE) {
                        $stream->next();
                        $node['children'] = View::parseTemplate($stream, ($depth + 1));
                    } {
                        // EXCEPTION.
                    }

                    $nodes[] = $node;
                    $blockOpenCount++;
                break;

                case 'embed':
                    $stream->next();

                    $node['type'] = $blockName;
                    if ($stream->getCurrent()->getType() === \Twig_Token::STRING_TYPE) {
                        $node['code'] = $stream->getCurrent()->getValue();
                    } {
                        // EXCEPTION.
                    }

                    $stream->next();
                    if ($stream->getCurrent()->getType() === \Twig_Token::BLOCK_END_TYPE) {
                        $stream->next();
                        $node['children'] = View::parseTemplate($stream, ($depth + 1));
                    } {
                        // EXCEPTION.
                    }

                    $nodes[] = $node;
                    $embedOpenCount++;
                break;

                case 'extends':
                case 'include':
                    $stream->next();

                    $node['type'] = $blockName;
                    if ($stream->getCurrent()->getType() === \Twig_Token::STRING_TYPE) {
                        $node['code'] = $stream->getCurrent()->getValue();
                        $stream->next();
                    } {
                        // EXCEPTION.
                    }

                    while ($stream->getCurrent()->getType() !== \Twig_Token::BLOCK_END_TYPE) {
                        $stream->next();
                    }

                    $stream->next();
                    $node['children'] = [];
                    $nodes[]          = $node;
                break;

                case 'endblock':
                    $blockOpenCount--;
                    if ($blockOpenCount < 0) {
                        return $nodes;
                    }
                break;

                case 'endembed':
                    $embedOpenCount--;
                    if ($embedOpenCount < 0) {
                        return $nodes;
                    }
                break;

                default:
                    $stream->next();
                break;
            }//end switch
        }//end while

        return $nodes;

    }//end parseTemplate()


    /**
     * Import YAML spec.
     *
     * @param string $project The project we are using.
     *
     * @return boolean
     * @throws \Exception When unable to get API Paths.
     */
    public static function installViews(string $project)
    {
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $project = str_replace('\\', '/', $project);
            $dir     = substr(FileSystem::getProjectDir($GLOBALS['project']), 0, -4);

            $routeFile =$dir.'/vendor/'.$project.'/src/web/routes/yaml';
        } else {
            $routeFile = FileSystem::getProjectDir($project).'/web/routes.yaml';
        }

        if (file_exists($routeFile) === false) {
            // No view routes so nothing to do.
            return true;
        }

        ini_set('yaml.decode_php', 0);
        $yaml   = file_get_contents($routeFile);
        $routes = \Symfony\Component\Yaml\Yaml::parse($yaml);
        if ($routes === false || empty($routes['routes']) === true) {
            throw new \Exception('Failed to parse View routes.');
        }

        // Bake the simulator router and handler.
        self::bakeRouter($routes['routes'], $project);
        self::bakeHandler($project);

        return true;

    }//end installViews()


    /**
     * Bakes router class.
     *
     * @param array  $routes  The routes for the project.
     * @param string $project The project the router belongs to.
     *
     * @return void
     */
    private static function bakeRouter(array $routes, string $project)
    {
        $tempFile = dirname(__FILE__).'/ViewRouter.temp.php';
        if (file_exists($tempFile) === false) {
            throw new \Exception('Missing router template file.');
        }

        $routeCode = '';
        foreach ($routes as $route) {
            $tempCode = '$r->addRoute(\'GET\', \''.$route['path'].'\', ';
            if ($route['type'] === 'custom' && isset($route['handler']) === true) {
                $tempCode .= '[\'\\'.$project.'\\WebHandler\', \''.$route['handler'].'\']';
            } else if ($route['type'] === 'view' && isset($route['view']) === true) {
                $tempCode .= '[\'self::render\', \''.$route['view'].'\']';
            }
            $tempCode .= ');';

            $routeCode .= Util::printCode(3, $tempCode);
        }

        $code = file_get_contents($tempFile);
        $code = str_replace('__CLASS_NAMESPACE__', $project, $code);
        $code = str_replace('__ROUTES__', $routeCode, $code);

        $prefix = Bootstrap::generatePrefix($project);
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $routerFile = FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/'.$prefix.'-viewrouter.php';
        } else {
            $routerFile = FileSystem::getSimulatorDir().'/'.$project.'/'.$prefix.'-viewrouter.php';
        }

        file_put_contents($routerFile, $code);

    }//end bakeRouter()


    /**
     * Bakes Web Handler class.
     *
     * @param string $project The project we are baking.
     *
     * @return void
     */
    private static function bakeHandler(string $project)
    {
        $code  = Util::printCode(0, '<?php');
        $code .= Util::printCode(0, 'namespace '.$project.';');
        $code .= Util::printCode(0, '');
        $code .= Util::printCode(0, 'class WebHandler');
        $code .= Util::printCode(0, '{');
        $code .= Util::printCode(0, '');
        $code .= Util::printCode(0, '');

        $handlerFunctions = scandir(FileSystem::getProjectDir().'/web/handlers');
        foreach ($handlerFunctions as $file) {
            if ($file[0] === '.' || substr($file, -4) !== '.php') {
                continue;
            }

            $filename = str_replace('.php', '', $file);
            $code    .= Util::printCode(1, 'public static function '.$filename.'()');
            $code    .= Util::printCode(1, '{');
            $code    .= Util::printCode(2, '$content = \PerspectiveSimulator\View\View::getHandlerFunction(__NAMESPACE__, \''.$filename.'\');');
            $code    .= Util::printCode(2, 'return eval($content);');
            $code    .= Util::printCode(1, '}');
            $code    .= Util::printCode(0, '');
            $code    .= Util::printCode(0, '');
        }

        $code .= Util::printCode(0, '}//end class');

        $prefix = Bootstrap::generatePrefix($project);
        if (strtolower($GLOBALS['project']) !== strtolower($project)) {
            $handlerFile = FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/'.$prefix.'-webhandler.php';
        } else {
            $handlerFile = FileSystem::getSimulatorDir().'/'.$project.'/'.$prefix.'-webhandler.php';
        }

        file_put_contents($handlerFile, $code);

    }//end bakeHandler()


    /**
     * Gets the file path for the Handler function.
     *
     * @param string $project The namespace of the project we want the action from.
     * @param string $action  The action we want to perform.
     *
     * @return string
     * @throws \Exception When the Handler operation doesn't exist.
     */
    public static function getHandlerFunction(string $project, string $action)
    {
        $file = FileSystem::getProjectDir().'/web/handlers/'.$action.'.php';
        if (is_file($file) === false) {
            throw new \Exception('Handler operation "'.$action.'" does not exist');
        }

        $content = file_get_contents($file);
        $content = str_replace('<?php', '', $content);
        return $content;

    }//end getHandlerFunction()


}//end class
