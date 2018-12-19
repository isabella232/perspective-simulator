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
                \PerspectiveSimulator\Bootstrap::processSave();
            }
        );

        $GLOBALS['projectNamespace'] = $project;
        $GLOBALS['project']          = str_replace('\\', '/', $project);
        $projectDir                  = Libs\FileSystem::getProjectDir();

        // Register an autoloader for the project.
        $loader = include dirname(__DIR__, 3).'/autoload.php';
        $loader->addPsr4($project.'\\', $projectDir);

        class_alias('PerspectiveSimulator\Storage\StorageFactory', $project.'\API\Operations\StorageFactory');
        class_alias('PerspectiveSimulator\Requests\Request', $project.'\API\Operations\Request');
        class_alias('PerspectiveSimulator\ObjectType\DataRecord', $project.'\CustomTypes\Data\DataRecord');
        class_alias('PerspectiveSimulator\View\ViewBase', $project.'\Web\Views\View');

        if (class_exists('\Authentication') === false) {
            class_alias('PerspectiveSimulator\View\ViewBase', '\View');
            class_alias('PerspectiveSimulator\Authentication', '\Authentication');
            class_alias('PerspectiveSimulator\Storage\StorageFactory', '\StorageFactory');
            class_alias('PerspectiveSimulator\Requests\Request', '\Request');
            class_alias('PerspectiveSimulator\Requests\Session', '\Session');
            class_alias('PerspectiveSimulator\Queue\Queue', '\Queue');
            class_alias('PerspectiveSimulator\Libs\Email', '\Email');
        }

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

        $prefix = self::generatePrefix($project);

        // Add data record properties.
        if (is_dir($projectDir.'/Properties/Data') === true) {
            $files = scandir($projectDir.'/Properties/Data');
            foreach ($files as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) !== '.json'
                ) {
                    continue;
                }

                $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Data/'.$file));
                StorageFactory::createDataRecordProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
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

                $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Project/'.$file));
                StorageFactory::createProjectProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
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

                $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/User/'.$file));
                StorageFactory::createUserProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
            }
        }

        // Add default user properties.
        StorageFactory::createUserProperty($prefix.'-__first-name__', 'text');
        StorageFactory::createUserProperty($prefix.'-__last-name__', 'text');

        \PerspectiveSimulator\Requests\Session::load();
        \PerspectiveSimulator\Queue\Queue::load();

        self::loadDependencies($project);

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
     * Loads a projects dependencies to the simulator.
     *
     * @param string $mainProject The project we are loading dependencies for.
     *
     * @return void
     */
    private static function loadDependencies(string $mainProject)
    {
        $path     = substr(Libs\FileSystem::getProjectDir(), 0, -4);
        $composer = $path.'/composer.json';
        if (file_exists($composer) === true) {
            $requirements     = [];
            $composerContents = Libs\Util::jsonDecode(file_get_contents($composer));
            if (isset($composerContents['require']) === true) {
                $requirements = array_merge($requirements, $composerContents['require']);
            }

            if (isset($composerContents['require-dev']) === true) {
                $requirements = array_merge($requirements, $composerContents['require-dev']);
            }

            if (empty($requirements) === true) {
                // No dependencies to load so just return.
                return;
            }

            foreach ($requirements as $requirement => $version) {
                $project    = str_replace('/', '\\', $requirement);
                $projectDir = $path.'/vendor/'.str_replace('\\', '/', $requirement).'/src';
                $prefix     = self::generatePrefix($project);

                class_alias('PerspectiveSimulator\Storage\StorageFactory', $project.'\API\Operations\StorageFactory');
                class_alias('PerspectiveSimulator\Requests\Request', $project.'\API\Operations\Request');
                class_alias('PerspectiveSimulator\ObjectType\DataRecord', $project.'\CustomTypes\Data\DataRecord');
                class_alias('PerspectiveSimulator\View\ViewBase', $project.'\Web\Views\View');

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

                        $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                        $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Data/'.$file));
                        StorageFactory::createDataRecordProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
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

                        $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                        $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/Project/'.$file));
                        StorageFactory::createProjectProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
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

                        $propName = $prefix.'-'.strtolower(substr($file, 0, -5));
                        $propInfo = Libs\Util::jsonDecode(file_get_contents($projectDir.'/Properties/User/'.$file));
                        StorageFactory::createUserProperty($propName, $propInfo['type'], ($propInfo['default'] ?? null));
                    }
                }
            }//end foreach
        }//end if

    }//end loadDependencies()


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

        foreach (self::$saveQueue as $object) {
            if (method_exists($object, 'save') === true) {
                $object->save();
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


}//end class
