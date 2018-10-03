<?php
namespace PerspectiveSimulator;

class Bootstrap {

    private static $readEnabled  = true;
    private static $writeEnabled = true;


    public static function load($project)
    {
        $projectDir = dirname(__DIR__, 4).'/Projects/'.$project;

        // Register an autoloader for the project.
        $loader = require dirname(__DIR__, 3).'/autoload.php';
        $loader->addPsr4('Commenting\\', $projectDir);

        class_alias('PerspectiveSimulator\StorageFactory', $project.'\API\Operations\StorageFactory');
        class_alias('PerspectiveSimulator\Request', $project.'\API\Operations\Request');

        class_alias('PerspectiveSimulator\StorageFactory', 'StorageFactory');
        class_alias('PerspectiveSimulator\Request', 'Request');

        class_alias('PerspectiveSimulator\DataRecord', $project.'\CustomTypes\Data\DataRecord');

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

    public static function isReadEnabled()
    {
        return self::$readEnabled;
    }
    public static function disableRead()
    {
        self::$readEnabled = false;
    }
    public static function enableRead()
    {
        self::$readEnabled = true;
    }
    public static function isWriteEnabled()
    {
        return self::$writeEnabled;
    }
    public static function disableWrite()
    {
        self::$writeEnabled = false;
    }
    public static function enableWrite()
    {
        self::$writeEnabled = true;
    }


}//end class
