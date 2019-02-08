<?php
/**
 * Bootstrap class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

use \PerspectiveAPI\Storage\StorageFactory;
use \PerspectiveSimulator\Libs;

/**
 * Bootstrap class
 */
class Bootstrap
{

    /**
     * Read enabled flag.
     *
     * @var boolean
     */
    private static $saveQueue = [];

    /**
     * Read enabled flag.
     *
     * @var boolean
     */
    private static $readEnabled = true;

    /**
     * Write enabled flag.
     *
     * @var boolean
     */
    private static $writeEnabled = true;

    /**
     * Notifications enabled flag.
     *
     * @var boolean
     */
    private static $notificationsEnabled = true;


    /**
     * Loads classes for the project.
     *
     * @param string $project The namesapce of the Project.
     *
     * @return void
     */
    public static function load(string $project)
    {
        // Register the shutdown function to process any saves that we have queued.
        register_shutdown_function(
            function () {
                $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
                $simulatorHandler->save();
            }
        );

        $GLOBALS['projectNamespace'] = $project;
        $GLOBALS['project']          = str_replace('\\', '/', $project);
        $GLOBALS['projectPath']      = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $project));
        $projectDir                  = Libs\FileSystem::getProjectDir();

        // Register an autoloader for the project.
        $loader = include dirname(__DIR__, 3).'/autoload.php';
        $loader->addPsr4($project.'\\', $projectDir);

        // First, set the connector alias.
        if (class_exists('\PerspectiveAPI\Connector') === false) {
            \PerspectiveAPI\Init::setConnectorAlias('PerspectiveSimulator\SimulatorConnector');
        }

        // Set up Perspective API class aliases for simulator execution.
        $perspectiveAPIClassAliases = [
            'PerspectiveAPI\Authentication'              => '\Authentication',
            'PerspectiveAPI\Email'                       => '\Email',
            'PerspectiveAPI\Request'                     => '\Request',
            'PerspectiveAPI\Queue'                       => '\Queue',
            'PerspectiveAPI\Session'                     => '\Session',
            'PerspectiveAPI\Storage\StorageFactory'      => '\StorageFactory',
            'PerspectiveAPI\Class\Types\ProjectInstance' => '\ProjectInstance',
        ];

        // Always alias theses classes if they haven't been already as we might be loading another project.
        $perspectiveAPIClassAliasesProject = [
            'PerspectiveAPI\Class\Types\DataRecord' => $project.'\CustomTypes\Data\DataRecord',
            'PerspectiveAPI\Class\Types\User'       => $project.'\CustomTypes\User\User',
            'PerspectiveAPI\Class\Types\Group'      => $project.'\CustomTypes\User\Group',
        ];

        if (class_exists($project.'\CustomTypes\User\Group') === false) {
            foreach ($perspectiveAPIClassAliasesProject as $orignalClass => $aliasClass) {
                class_alias($orignalClass, $aliasClass);
            }
        }

        if (class_exists($project.'\Web\Views\View') === false) {
            class_alias('PerspectiveSimulator\View\ViewBase', $project.'\Web\Views\View');
        }

        if (class_exists('\Authentication') === false) {
            class_alias('PerspectiveSimulator\View\ViewBase', '\View');

            foreach ($perspectiveAPIClassAliases as $orignalClass => $aliasClass) {
                class_alias($orignalClass, $aliasClass);
            }
        }

        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->load();

        \PerspectiveSimulator\Requests\Session::load();
        \PerspectiveSimulator\Queue\Queue::load();

    }//end load()


    /**
     * Gets the read enabled flag.
     *
     * @return boolean
     */
    public static function isReadEnabled()
    {
        return self::$readEnabled;

    }//end isReadEnabled()


    /**
     * Disables read of data written to file system.
     *
     * @return void
     */
    public static function disableRead()
    {
        self::$readEnabled = false;

    }//end disableRead()


    /**
     * Enables read of data written to file system.
     *
     * @return void
     */
    public static function enableRead()
    {
        self::$readEnabled = true;

    }//end enableRead()


    /**
     * Gets the is write enabled flag.
     *
     * @return boolean
     */
    public static function isWriteEnabled()
    {
        return self::$writeEnabled;

    }//end isWriteEnabled()


    /**
     * Disables writing of data to filesystem.
     *
     * @return void
     */
    public static function disableWrite()
    {
        self::$writeEnabled = false;

    }//end disableWrite()


    /**
     * Enables writing of data to filesystem.
     *
     * @return void
     */
    public static function enableWrite()
    {
        self::$writeEnabled = true;

    }//end enableWrite()


    /**
     * Gets the is write enabled flag.
     *
     * @return boolean
     */
    public static function isNotificationsEnabled()
    {
        return self::$notificationsEnabled;

    }//end isNotificationsEnabled()


    /**
     * Disables writing of data to filesystem.
     *
     * @return void
     */
    public static function disableNotifications()
    {
        self::$notificationsEnabled = false;

    }//end disableNotifications()


    /**
     * Enables writing of data to filesystem.
     *
     * @return void
     */
    public static function enableNotifications()
    {
        self::$notificationsEnabled = true;

    }//end enableNotifications()


    /**
     * Generates a prefix.
     *
     * @param string $project The project to get the prefix for.
     *
     * @return string
     */
    public static function generatePrefix(string $project)
    {
        $project = str_replace('\\', '-', $project);
        $project = str_replace('/', '-', $project);
        return strtolower($project);

    }//end generatePrefix()


    /**
     * Queues a save for later.
     *
     * @param object $object Object to be added to the save queue
     *
     * @return void
     */
    public static function queueSave($object)
    {
        self::$saveQueue[] = $object;

    }//end queueSave


    /**
     * Process the save queue.
     *
     * @return void
     */
    public static function processSave()
    {
        if (empty(self::$saveQueue) === true) {
            return;
        }

        // Only need to save when write is enabled.
        if (self::$writeEnabled === true) {
            foreach (self::$saveQueue as $object) {
                if (method_exists($object, 'save') === true) {
                    $object->save();
                }
            }
        }

        self::clearSaveQueue();

    }//end processSave()


    /**
     * Clears the save queue
     *
     * @return void
     */
    public static function clearSaveQueue()
    {
        self::$saveQueue = [];

    }//end clearSaveQueue()


    public static function getProjectPrefix()
    {
        $bt = debug_backtrace(false);

        // Remove the call to this and the call to the function that needs the property code prefixed.
        array_shift($bt);
        array_shift($bt);

        $key = 0;
        foreach ($bt as $id => $call) {
            if ($call['function'] === 'eval') {
                $key = ($id + 1);
                break;
            }
        }

        $called = $bt[$key];
        if (isset($called['class']) === true && strpos(strtolower($GLOBALS['project']), strtolower($called['class'])) !== false) {
            $classParts   = explode('\\', $called['class']);
            return Bootstrap::generatePrefix($classParts[0].'\\'.$classParts[1]);
        } else {
            return Bootstrap::generatePrefix($GLOBALS['project']);
        }

    }//end getProjectPrefix()


}//end class
