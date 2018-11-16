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
        session_start();

        $project            = ucfirst($project);
        $GLOBALS['project'] = $project;
        $projectDir         = Libs\FileSystem::getProjectDir();

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
        if (is_dir($projectDir.'/Properties/Data') === true) {
            $files = scandir($projectDir.'/Properties/Data');
            foreach ($files as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) !== '.json'
                ) {
                    continue;
                }

                $propName = strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Data/'.$file));
                StorageFactory::createDataRecordProperty($propName, $propInfo['type']);
            }
        }

        // Add project properties.
        if (is_dir($projectDir.'/Properties/Project') === true) {
            $files = scandir($projectDir.'/Properties/Project');
            foreach ($files as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) !== '.json'
                ) {
                    continue;
                }

                $propName = strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Project/'.$file));
                StorageFactory::createDeployementProperty($propName, $propInfo['type']);
            }
        }

        // Add user properties.
        if (is_dir($projectDir.'/Properties/User') === true) {
            $files = scandir($projectDir.'/Properties/User');
            foreach ($files as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) !== '.json'
                ) {
                    continue;
                }

                $propName = strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/User/'.$file));
                StorageFactory::createUserProperty($propName, $propInfo['type']);
            }
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
     * Installs the simulator for us.
     *
     * @return void
     */
    public static function install()
    {
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            Libs\FileSystem::mkdir($simulatorDir);
        }

        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $GLOBALS['project'] = $project;

            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                if (is_dir($simulatorDir.'/'.$project) === false) {
                    Libs\FileSystem::mkdir($simulatorDir.'/'.$project);
                }

                $projectKey = Authentication::generateSecretKey();

                $storageDir = Libs\FileSystem::getStorageDir($project);
                if (is_dir($storageDir) === false) {
                    Libs\FileSystem::mkdir($storageDir);
                }

                API::installAPI($project);
            }
        }//end foreach

    }//end install()


}//end class
