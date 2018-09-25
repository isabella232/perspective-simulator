<?php
namespace PerspectiveSimulator;

class Bootstrap {

    private static $readEnabled  = true;
    private static $writeEnabled = true;


    public static function load($project)
    {
        $projectDir = dirname(dirname(dirname(dirname(__DIR__)))).'/Projects/'.$project;

        // Register an autoloader for the project.
        $loader = require dirname(dirname(dirname(__DIR__))).'/autoload.php';
        $loader->addPsr4('Commenting\\', $projectDir);

        class_alias('PerspectiveSimulator\StorageFactory', $project.'\API\StorageFactory');
        class_alias('PerspectiveSimulator\DataRecord', $project.'\CustomTypes\Data\DataRecord');

        // Add data stores.
        $files = scandir($projectDir.'/Stores/Data');
        foreach ($files as $file) {
            if ($file[0] === '.'
                || substr($file, -5) !== '.json'
            ) {
                continue;
            }

            $storeName = strtolower(substr($file, 0, -5));
            StorageFactory::createDataStore($storeName, $project);
        }

        // Add data record properties.
        $files = scandir($projectDir.'/Properties/DataRecord');
        foreach ($files as $file) {
            if ($file[0] === '.'
                || substr($file, -5) !== '.json'
            ) {
                continue;
            }

            $propName = strtolower(substr($file, 0, -5));
            $propInfo = json_decode(file_get_contents($projectDir.'/Properties/DataRecord/'.$file), true);
            StorageFactory::createDataRecordProperty($propName, $propInfo['type']);
        }

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
