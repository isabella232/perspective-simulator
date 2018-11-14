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

use \PerspectiveSimulator\Storage\StorageFactory;
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
    private static $readEnabled = true;

    /**
     * Write enabled flag.
     *
     * @var boolean
     */
    private static $writeEnabled = true;


    /**
     * Loads classes for the project.
     *
     * @param string $project The namesapce of the Project.
     *
     * @return void
     */
    public static function load(string $project)
    {
        $project            = ucfirst($project);
        $GLOBALS['project'] = $project;
        $projectDir = dirname(__DIR__, 4).'/projects/'.$project;

        // Register an autoloader for the project.
        $loader = include dirname(__DIR__, 3).'/autoload.php';
        $loader->addPsr4($project.'\\', $projectDir);

        class_alias('PerspectiveSimulator\Storage\StorageFactory', $project.'\API\Operations\StorageFactory');
        class_alias('PerspectiveSimulator\Requests\Request', $project.'\API\Operations\Request');
        class_alias('PerspectiveSimulator\ObjectType\DataRecord', $project.'\CustomTypes\Data\DataRecord');

        class_alias('PerspectiveSimulator\Authentication', '\Authentication');
        class_alias('PerspectiveSimulator\Storage\StorageFactory', '\StorageFactory');
        class_alias('PerspectiveSimulator\Requests\Request', '\Request');
        class_alias('PerspectiveSimulator\Requests\Session', '\Session');

        // Add data stores.
        $dirs = glob($projectDir.'/Stores/Data/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $storeName = strtolower(basename($dir));
            StorageFactory::createDataStore($storeName, $project);
        }

        // Add user stores.
        $dirs = glob($projectDir.'/Stores/User/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $storeName = strtolower(basename($dir));
            StorageFactory::createUserStore($storeName, $project);
        }

        // Add data record properties.
        $files = scandir($projectDir.'/Properties/Data');
        foreach ($files as $file) {
            if ($file[0] === '.'
                || substr($file, -5) !== '.json'
            ) {
                continue;
            }

            $propName = strtolower(substr($file, 0, -5));
            $propInfo = json_decode(file_get_contents($projectDir.'/Properties/Data/'.$file), true);
            StorageFactory::createDataRecordProperty($propName, $propInfo['type']);
        }

        // Add default user properties.
        StorageFactory::createUserProperty('__first-name__', 'text');
        StorageFactory::createUserProperty('__last-name__', 'text');

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
     * Gets the storage directory.
     *
     * @return string
     */
    public static function getSimulatorDir()
    {
        return dirname(__DIR__, 4).'/simulator';

    }//end getSimulatorDir()


    /**
     * Gets the storage directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getStorageDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        if (self::isReadEnabled() === false && self::isWriteEnabled() === false) {
            return null;
        }

        return self::getSimulatorDir().'/'.$project.'/storage';

    }//end getStorageDir()


    /**
     * Gets the project directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getProjectDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        return dirname(__DIR__, 4).'/projects/'.$project;

    }//end getProjectDir()


    /**
     * Installs the simulator for us.
     *
     * @return void
     */
    public static function install()
    {
        $simulatorDir = self::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            mkdir($simulatorDir);
        }

        $projectPath = dirname(__DIR__, 4).'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                if (is_dir($simulatorDir.'/'.$project) === false) {
                    mkdir($simulatorDir.'/'.$project);
                }

                $projectKey = Authentication::generateSecretKey();
                file_put_contents(
                    $simulatorDir.'/'.$project.'/authentication.json',
                    Libs\Util::jsonEncode(['secretKey' => $projectKey])
                );

                $storageDir = self::getStorageDir($project);
                if (is_dir($storageDir) === false) {
                    mkdir($storageDir);
                }

                API::installAPI($project);
            }
        }

    }//end install()


}//end class
