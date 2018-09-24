<?php

namespace PerspectiveSimulator;

if (class_exists('PerspectiveSimulator\Autoload', false) === false) {
    class Autoload
    {

        /**
         * Loads a class.
         *
         * @param string $class The name of the class to load.
         *
         * @return bool
         */
        public static function load($class)
        {
            if (substr($class, -4) === '\API' && substr_count($class, '\\') === 1) {
                $project = substr($class, 0, strpos($class, '\\'));
                $file = dirname(dirname(dirname(__DIR__))).'/simulator/'.$project.'/API.php';
                include $file;
                return true;
            }

            if (substr($class, -10) === '\APIRouter' && substr_count($class, '\\') === 1) {
                $project = substr($class, 0, strpos($class, '\\'));
                $file = dirname(dirname(dirname(__DIR__))).'/simulator/'.$project.'/APIRouter.php';
                include $file;
                return true;
            }

            return false;

        }//end load()

    }//end class

    spl_autoload_register('PerspectiveSimulator\Autoload::load');
}//end if
