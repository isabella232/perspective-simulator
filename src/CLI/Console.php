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
                // phpcs:disable
                error_log(var_export([$errno, $errstr, $errfile, $errline], 1));
                // phpcs:enable
            }
        );

        if (isset($args['install']) === true && $args['install'] === true) {
            $command = new \PerspectiveSimulator\CLI\Command\Install('install', []);
            $command->install();
        } else {
            if (isset($args['argv']) === true) {
                self::getArgs($args['argv']);
            }

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
                throw new CLIException(_('Print help'));
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
        self::$args        = $args;

    }//end getArgs()


    /**
     * Loads the project for the CLI
     *
     * @param string $project The name of the project.
     *
     * @return void
     */
    public static function loadProject(string $project=null)
    {
        if ($project === null) {
            // Check that theres only 1 project if so we can use that otherwise throw error.
            try {
                $project = self::getProject();
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
    private static function getProject()
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
            $eMsg  = 'Multiple projects found, perspective command expects project to be set when more than 1 project';
            $eMsg .= " in export.\n";
            $eMsg .= 'Try running perspective -p <project name> '.self::$actionName.' '.self::$commandName;
            $eMsg .= ' '.implode(' ', self::$args)."\n";
            throw new CLIException($eMsg);
        }

        return $project;

    }//end getProject()


}//end class
