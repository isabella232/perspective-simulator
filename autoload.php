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
            if (strpos($class, '\DataRecordType\\') !== false) {
                $type = substr($class, (strrpos($class, '\\') + 1));
                if ($type === 'DataRecord') {
                    return false;
                }

                $project = substr($class, 0, strpos($class, '\\'));
                $file = dirname(dirname(dirname(__DIR__))).'/Projects/'.$project.'/CustomTypes/DataRecord/'.$type.'.php';
                include $file;
                return true;
            }

            return false;

        }//end load()

    }//end class

    spl_autoload_register('PerspectiveSimulator\Autoload::load');
}//end if
