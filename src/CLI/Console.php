<?php
/**
 * Console class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI;

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\Exceptions\CLIException;

/**
 * CLI Console Class
 */
class Console
{

    /**
     * Singleton instance of this console object.
     *
     * @var object
     */
    private static $instance;

    /**
     * The script name of the current script.
     *
     * @var string
     */
    private static $scriptName = null;

    /**
     * The action we are running.
     *
     * @var string
     */
    private static $actionName = null;

    /**
     * The command we are running.
     *
     * @var string
     */
    private static $commandName = null;

    /**
     * Array of args for the action.
     *
     * @var array
     */
    private static $args = null;


    /**
     * Constructor
     */
    public function __construct()
    {

    }//end __construct()


    /**
     * Returns or instantiates a singleton instance of this console object.
     *
     * @return object
     */
    public static function getInstance()
    {
        if (isset(self::$instance) === false) {
            self::$instance = new Console();
        }

        return self::$instance;

    }//end getInstance()


    /**
     * Run an interactive command line action.
     *
     * @param array $args Array of arguments for the script to use.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function run(array $args)
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                $errorType = 'PERSPECTIVE_SIMULATOR_ERROR';
                switch ($errno) {
                    case E_ERROR:
                        $errorType = 'E_ERROR';
                    break;

                    case E_WARNING:
                        $errorType = 'E_WARNING';
                    break;

                    case E_PARSE:
                        $errorType = 'E_PARSE';
                    break;

                    case E_NOTICE:
                        $errorType = 'E_NOTICE';
                    break;

                    case E_CORE_ERROR:
                        $errorType = 'E_CORE_ERROR';
                    break;

                    case E_CORE_WARNING:
                        $errorType = 'E_CORE_WARNING';
                    break;

                    case E_COMPILE_ERROR:
                        $errorType = 'E_COMPILE_ERROR';
                    break;

                    case E_COMPILE_WARNING:
                        $errorType = 'E_COMPILE_WARNING';
                    break;

                    case E_USER_ERROR:
                        $errorType = 'E_USER_ERROR';
                    break;

                    case E_USER_WARNING:
                        $errorType = 'E_USER_WARNING';
                    break;

                    case E_USER_NOTICE:
                        $errorType = 'E_USER_NOTICE';
                    break;

                    case E_STRICT:
                        $errorType = 'E_STRICT';
                    break;

                    case E_RECOVERABLE_ERROR:
                        $errorType = 'E_RECOVERABLE_ERROR';
                    break;

                    case E_DEPRECATED:
                        $errorType = 'E_DEPRECATED';
                    break;

                    case E_USER_DEPRECATED:
                        $errorType = 'E_USER_DEPRECATED';
                    break;

                    default:
                        $errorType = 'PERSPECTIVE_SIMULATOR_ERROR';
                    break;
                }//end switch

                $size             = Terminal::getSize();
                $indent           = str_repeat(' ', 4);
                $optionMaxWidth   = 30;
                $errorTypeConsole = Terminal::formatText(Terminal::colourText($errorType, 'red'), ['bold']);

                Terminal::printHeader(Terminal::formatText('Perspective Simulator Error.', ['bold']));
                Terminal::write(Terminal::padTo($indent.$errorTypeConsole, $optionMaxWidth, ' '));
                Terminal::printError(
                    Terminal::wrapText(
                        Terminal::colourText($errstr.' '.$errfile.':'.$errline, 'red'),
                        $size['cols'],
                        ' ',
                        $optionMaxWidth,
                        4,
                        false
                    )
                );
            }
        );

        if (isset($args['install']) === true && $args['install'] === true) {
            $command = new \PerspectiveSimulator\CLI\Command\Install('install', []);
            if (isset($args['help']) === true && $args['help'] === true) {
                $command->printHelp(self::$actionName);
                exit(1);
            }

            $command->install();
        } else if (isset($args['clean']) === true && $args['clean'] === true) {
            $command = new \PerspectiveSimulator\CLI\Command\Clean('clean', []);
            if (isset($args['help']) === true && $args['help'] === true) {
                $command->printHelp(self::$actionName);
                exit(1);
            }

            $command->clean();
        } else {
            $actionClass = '\\PerspectiveSimulator\\CLI\\Command\\'.self::$commandName;
            if (class_exists($actionClass) === true) {
                try {
                    $command = new $actionClass(self::$actionName, self::$args);
                    if (method_exists($command, self::$actionName) === false) {
                        $command->printHelp();
                        exit(1);
                    }

                    if (isset($args['help']) === true && $args['help'] === true) {
                        $command->printHelp(self::$actionName);
                        exit(1);
                    }

                    $funcName = self::$actionName;
                    $command->$funcName();
                } catch (CLIException $e) {
                    $e->prettyPrint();
                    exit(1);
                } catch (\Exception $e) {
                    $size = Terminal::getSize();
                    Terminal::printError(
                        Terminal::wrapText($e->getMessage(), $size['cols'])
                    );
                    exit(1);
                }//end try
            } else {
                $size           = Terminal::getSize();
                $indent         = str_repeat(' ', 4);
                $optionMaxWidth = 30;
                $commands       = Libs\FileSystem::listDirectory(
                    dirname(__FILE__).'/Command/',
                    ['.php']
                );

                Terminal::printHeader(
                    sprintf(
                        'Help for: %s',
                        Terminal::formatText('perspective [options] <action> <command> <arguments>', ['bold'])
                    )
                );

                $options = [
                    [
                        '-p',
                        '--project',
                        'Specifies the project to perform the action on.',
                    ],
                    [
                        '-h',
                        '--help',
                        'Shows the help screen for the action.',
                    ],
                    [
                        '-i',
                        '--install',
                        'Installs the simulator, runs only when simulator directory doesn\'t exist.',
                    ],
                    [
                        '-S',
                        '--server',
                        'Starts the PHP development server for the simulator.',
                    ],
                ];

                foreach ($options as $option) {
                    Terminal::write(Terminal::padTo($indent.$option[0], $optionMaxWidth, ' '));
                    Terminal::printLine(
                        Terminal::wrapText(
                            $option[2],
                            $size['cols'],
                            ' ',
                            $optionMaxWidth,
                            4,
                            false
                        )
                    );
                    Terminal::printLine($indent.$option[1]);
                    Terminal::printLine();
                }

                foreach ($commands as $command) {
                    Terminal::printLine();
                    $commandParts = explode(DIRECTORY_SEPARATOR, $command);

                    $name      = str_replace('.php', '', end($commandParts));
                    $fullClass = '\\PerspectiveSimulator\\CLI\\Command\\'.$name;
                    $com       = new $fullClass('help', self::$args);
                    $com->printHelp();
                    Terminal::printLine();
                }
            }//end if
        }//end if

        restore_error_handler();

    }//end run()


    /**
     * Gets the args from argv passed through.
     *
     * @param array $args Array of script arguments.
     *
     * @return void
     */
    private static function getArgs(array $args)
    {
        // Filter out any options we don't want -h or -p.
        $args = array_filter(
            $args,
            function ($arg) {
                return $arg[0] !== '-';
            }
        );

        self::$scriptName  = array_shift($args);
        self::$actionName  = array_shift($args);
        self::$commandName = array_shift($args);

        if (self::$commandName === 'property') {
            self::$commandName = array_shift($args).self::$commandName;
        } else if (self::$commandName === 'customtype') {
            $customType        = array_shift($args);
            self::$commandName = 'CustomDataType';
            if ($customType === 'page') {
                self::$commandName = 'CustomPageType';
            }
        }

        self::$args = $args;

    }//end getArgs()


    /**
     * Loads the project for the CLI
     *
     * @param string $project The name of the project.
     * @param array  $args    The arguments passed in for the action we will perform later.
     *
     * @return void
     */
    public function loadProject(string $project=null, array $args=[])
    {
        if (isset($args['argv']) === true) {
            self::getArgs($args['argv']);
        }

        if (self::$commandName === 'project' || $args['help'] === true) {
            // We don't have a project so nothing to load in the simulator.
            return;
        }

        if ($project === null) {
            // Check that theres only 1 project if so we can use that otherwise throw error.
            try {
                $project = $this->getProject();
            } catch (CLIException $e) {
                $e->prettyPrint();
                exit;
            }
        }

        // Load the simulator.
        \PerspectiveSimulator\Bootstrap::load($project);

    }//end loadProject()


    /**
     * Gets the project from the projects directory, if more than one project will return null and let run deal with it.
     *
     * @return mixed
     * @throws CLIException When more than 1 project found and -p not specified.
     */
    private function getProject()
    {
        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                $projects[] = $project;
            }
        }//end foreach

        if (count($projects) > 1) {
            $msg          = 'Confirm which project you want to perform the action on.';
            $suppressMsg  = 'Alternatively you can run the command with';
            $suppressMsg .= '-p=<project name> or --project=<project name> to supress this message.';
            Terminal::printHeader('Multiple projects found.');
            Terminal::printLine($suppressMsg);
            $project = Prompt::optionList($msg, $projects);

            if ($project === null) {
                throw new CLIException('Multiple projects found and invalid project selected.');
            }
        }

        return $project;

    }//end getProject()


}//end class
