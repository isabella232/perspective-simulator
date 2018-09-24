<?php
namespace PerspectiveSimulator;

class Bootstrap {


    public static function load($project)
    {
        $projectDir = dirname(dirname(dirname(dirname(__DIR__)))).'/Projects/'.$project;

        // Add data stores.
        $files = scandir($projectDir.'/Stores/Data');
        foreach ($files as $file) {
            if ($file[0] === '.'
                || substr($file, -5) !== '.json'
            ) {
                continue;
            }

            $storeName = strtolower(substr($file, 0, -5));
            StorageFactory::createDataStore($storeName);
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


}//end class
