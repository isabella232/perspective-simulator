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

                // Simulate getting the cache report at the end of the request.
                \PerspectiveAPI\Cache::getCacheReport();
            }
        );

        $GLOBALS['project']     = str_replace('\\', '/', $project);
        $GLOBALS['projectPath'] = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $project));
        $projectDir             = Libs\FileSystem::getProjectDir();

        $path     = substr($projectDir, 0, -4);
        $composer = $path.'/composer.json';
        if (file_exists($composer) === true) {
            $composerFile = Libs\Util::jsonDecode(file_get_contents($composer));
            if (isset($composerFile['autoload']) === true) {
                foreach ($composerFile['autoload']['psr-4'] as $namespace => $dir) {
                    if (strpos($dir, 'src') === 0) {
                        $GLOBALS['projectNamespace'] = $namespace;
                        break;
                    }
                }
            } else {
                $GLOBALS['projectNamespace'] = str_replace('/', '\\', $project).'\\';
            }
        } else {
            // No composer file so we will attempt to use the project that was passed to load.
            $GLOBALS['projectNamespace'] = str_replace('/', '\\', $project).'\\';
        }

        $GLOBALS['projectDependencies'] = [];

        // Register an autoloader for the project.
        include dirname(__DIR__, 3).'/autoload.php';

        // First, set the connector alias.
        if (class_exists('\PerspectiveAPI\Connector') === false) {
            \PerspectiveAPI\Init::setConnectorAlias('PerspectiveSimulator\SimulatorConnector');
        }

        // Set up Perspective API class aliases for simulator execution.
        $perspectiveAPIClassAliases = [
            'PerspectiveAPI\Authentication'                => 'Authentication',
            'PerspectiveAPI\Email'                         => 'Email',
            'PerspectiveAPI\Request'                       => 'Request',
            'PerspectiveAPI\Queue'                         => 'Queue',
            'PerspectiveAPI\Storage\StorageFactory'        => 'StorageFactory',
            'PerspectiveAPI\Objects\Types\ProjectInstance' => 'ProjectInstance',
            'PerspectiveAPI\Cache'                         => 'Cache',
        ];

        // Always alias theses classes if they haven't been already as we might be loading another project.
        $perspectiveAPIClassAliasesProject = [
            'PerspectiveAPI\Objects\Types\DataRecord' => $GLOBALS['projectNamespace'].'CustomTypes\Data\DataRecord',
            'PerspectiveAPI\Objects\Types\User'       => $GLOBALS['projectNamespace'].'CustomTypes\User\User',
            'PerspectiveAPI\Objects\Types\Group'      => $GLOBALS['projectNamespace'].'CustomTypes\User\Group',
        ];

        if (class_exists($GLOBALS['projectNamespace'].'CustomTypes\User\Group') === false) {
            foreach ($perspectiveAPIClassAliasesProject as $orignalClass => $aliasClass) {
                class_alias($orignalClass, $aliasClass);
            }
        }

        if (class_exists($GLOBALS['projectNamespace'].'Framework\Authentication') === false) {
            foreach ($perspectiveAPIClassAliases as $orignalClass => $aliasClass) {
                eval('namespace '.$GLOBALS['projectNamespace'].'Framework; class '.$aliasClass.' extends \\'.$orignalClass.' {}');
            }
        }

        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->load();

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
        $project = rtrim($project, '-');
        return strtolower($project);

    }//end generatePrefix()


    /**
     * Gets the project prefix from a code/name
     *
     * @param string $code The name/code to get the project prefix of
     *
     * @return string
     */
    public static function getProjectPrefix(string $code)
    {
        $parts = explode('/', $code);
        return Bootstrap::generatePrefix($parts[0].'\\'.$parts[1]);

    }//end getProjectPrefix()


    /**
     * Gets the property id and type from the code.
     *
     * @param string $propertyCode The property code we want the id and type from.
     *
     * @return array
     */
    public static function getPropertyInfo(string $propertyCode)
    {
        $allowedTypes = [
            'unique',
            'boolean',
            'datetime',
            'html',
            'integer',
            'number',
            'pageid',
            'recordset',
            'selection',
            'text',
            'userid',
            'image',
            'file',
        ];

        $codeParts  = explode('.', $propertyCode);
        $type       = array_pop($codeParts);
        $propertyid = implode('.', $codeParts);
        if (in_array($type, $allowedTypes) === false) {
            throw new \Exception('Invalid property type');
        }

        return [
            strtolower($propertyid),
            strtolower($type),
        ];

    }//end getPropertyInfo()



}//end class
