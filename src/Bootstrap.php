<?php
namespace PerspectiveSimulator;

class Bootstrap {


    public static function load($project)
    {
        // TODO: Go through project folder and load dynamically.
        StorageFactory::createDataStore('comments');
        StorageFactory::createDataRecordProperty('comment-content', 'text');
        StorageFactory::createDataRecordProperty('threadid', 'unique');

    }//end load()


}//end class
