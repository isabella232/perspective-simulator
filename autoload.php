<?php

namespace PerspectiveSimulator;

if (class_exists('PerspectiveSimulator\Autoload', false) === false) {
    class Autoload
    {

        /**
         * The composer autoloader.
         *
         * @var Composer\Autoload\ClassLoader
         */
        private static $composerAutoloader = null;


        /**
         * Loads a class.
         *
         * @param string $class The name of the class to load.
         *
         * @return bool
         */
        public static function load($class)
        {
            if (self::$composerAutoloader === null) {
                // Make sure we don't try to load any of Composer's classes
                // while the autoloader is being setup.
                if (strpos($class, 'Composer\\') === 0) {
                    return;
                }

                if (isset($GLOBALS['project']) === true
                    && file_exists(dirname(__DIR__, 3).'/projects/'.$GLOBALS['project'].'/vendor/autoload.php') === true
                ) {
                    self::$composerAutoloader = include dirname(__DIR__, 3).'/projects/'.$GLOBALS['project'].'/vendor/autoload.php';
                    if (self::$composerAutoloader instanceof \Composer\Autoload\ClassLoader) {
                        self::$composerAutoloader->unregister();
                        self::$composerAutoloader->register();
                    } else {
                        // Something went wrong, so keep going without the autoloader.
                        self::$composerAutoloader = false;
                    }
                } else {
                    self::$composerAutoloader = false;
                }
            }//end if


            $ds   = DIRECTORY_SEPARATOR;
            $path = false;

            // See if the composer autoloader knows where the class is.
            if (self::$composerAutoloader !== false) {
                $path = self::$composerAutoloader->findFile($class);
            }

            if ($path === false) {
                $prefix = strtolower(str_replace('\\', '-', $class));
                if (substr($class, -4) === '\API' && substr_count($class, '\\') === 1) {
                    $project = substr($class, 0, strpos($class, '\\'));
                    $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/'.$prefix.'.php';
                } else if (isset($GLOBALS['project']) === true && substr($class, -4) === '\API' && strpos($class, $GLOBALS['project']) === false) {
                    $path = dirname(__DIR__, 3).'/simulator/'.$GLOBALS['project'].'/'.$prefix.'.php';
                }

                if (substr($class, -10) === '\APIRouter' && substr_count($class, '\\') === 1) {
                    $project = substr($class, 0, strpos($class, '\\'));
                    $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/'.$prefix.'.php';
                } else if (isset($GLOBALS['project']) === true && substr($class, -10) === '\APIRouter' && strpos($class, $GLOBALS['project']) === false) {
                    $path = dirname(__DIR__, 3).'/simulator/'.$GLOBALS['project'].'/'.$prefix.'.php';
                }

                if (substr($class, -11) === '\WebHandler' && substr_count($class, '\\') === 1) {
                    $project = substr($class, 0, strpos($class, '\\'));
                    $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/'.$prefix.'.php';
                } else if (isset($GLOBALS['project']) === true && substr($class, -11) === '\WebHandler' && strpos($class, $GLOBALS['project']) === false) {
                    $path = dirname(__DIR__, 3).'/simulator/'.$GLOBALS['project'].'/'.$prefix.'.php';
                }

                if (substr($class, -11) === '\ViewRouter' && substr_count($class, '\\') === 1) {
                    $project = substr($class, 0, strpos($class, '\\'));
                    $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/'.$prefix.'.php';
                } else if (isset($GLOBALS['project']) === true && substr($class, -11) === '\ViewRouter' && strpos($class, $GLOBALS['project']) === false) {
                    $path = dirname(__DIR__, 3).'/simulator/'.$GLOBALS['project'].'/'.$prefix.'.php';
                }


                if (substr($class, -9) === '\JobQueue' && substr_count($class, '\\') === 1) {
                    $project = substr($class, 0, strpos($class, '\\'));
                    $path    = dirname(__DIR__, 3).'/simulator/'.$project.'/'.$prefix.'.php';
                } else if (isset($GLOBALS['project']) === true && substr($class, -9) === '\JobQueue' && strpos($class, $GLOBALS['project']) === false) {
                    $path = dirname(__DIR__, 3).'/simulator/'.$GLOBALS['project'].'/'.$prefix.'.php';
                }

                if (substr($class, 0, 21) === 'PerspectiveSimulator\\') {
                    $parts = explode('\\', $class);
                    if ($parts[1] === 'StorageType') {
                        $path = __DIR__.$ds.'src'.$ds.'Storage'.$ds.'Types'.$ds.$parts[2].'.php';
                    } else  if ($parts[1] === 'ObjectType') {
                        $path = __DIR__.$ds.'src'.$ds.'Objects'.$ds.'Types'.$ds.$parts[2].'.php';
                    } else  if ($parts[1] === 'PropertyType') {
                        $path = __DIR__.$ds.'src'.$ds.'Property'.$ds.'Types'.$ds.$parts[2].'.php';
                    }
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
