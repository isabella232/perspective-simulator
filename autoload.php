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
            $ds   = DIRECTORY_SEPARATOR;
            $path = false;
            if (substr($class, -4) === '\API' && substr_count($class, '\\') === 1) {
                $project = substr($class, 0, strpos($class, '\\'));
                $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/API.php';
            }

            if (substr($class, -10) === '\APIRouter' && substr_count($class, '\\') === 1) {
                $project = substr($class, 0, strpos($class, '\\'));
                $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/APIRouter.php';
            }

            if (substr($class, 0, 21) === 'PerspectiveSimulator\\') {
                $parts = explode('\\', $class);
                if ($parts[1] === 'StorageType') {
                    $path = __DIR__.$ds.'src'.$ds.'Storage'.$ds.'Types'.$ds.$parts[2].'.php';
                } else  if ($parts[1] === 'ObjectType') {
                    $path = __DIR__.$ds.'src'.$ds.'Objects'.$ds.'Types'.$ds.$parts[2].'.php';
                }
            }

            if ($path !== false && is_file($path) === true) {
                include $path;
                return true;
            }

            return false;

        }//end load()

    }//end class

    spl_autoload_register('PerspectiveSimulator\Autoload::load');
}//end if
