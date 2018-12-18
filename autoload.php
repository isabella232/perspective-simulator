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

                $projectsFile = dirname(__DIR__, 3).'/simulator/projects.json';
                if (file_exists($projectsFile) === true && isset($GLOBALS['project']) === true) {
                    $projects          = json_decode(file_get_contents($projectsFile), true);
                    $project           = strtolower(str_replace('\\', '/', $GLOBALS['project']));
                    $projectAutoloader = null;
                    if (isset($projects[$project]) === true) {
                        $projectAutoloader = str_replace('/src', '/vendor/autoload.php', $projects[$project]);
                    }

                    if ($projectAutoloader !== null && file_exists($projectAutoloader) === true) {
                        self::$composerAutoloader = include $projectAutoloader;
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
                $projectNs = strtolower($GLOBALS['projectNamespace']);
                $prefix    = strtolower(str_replace('\\', '-', $class));

                if (strtolower($class) === $projectNs.'\\api') {
                    $path = dirname(__DIR__, 3).'/simulator/'.str_replace('\\', $ds, $GLOBALS['project']).'/'.$prefix.'.php';
                }

                if (strtolower($class) === $projectNs.'\\apirouter') {
                    $path = dirname(__DIR__, 3).'/simulator/'.str_replace('\\', $ds, $GLOBALS['project']).'/'.$prefix.'.php';
                }

                if (strtolower($class) === $projectNs.'\\webhandler') {
                    $path = dirname(__DIR__, 3).'/simulator/'.str_replace('\\', $ds, $GLOBALS['project']).'/'.$prefix.'.php';
                }

                if (strtolower($class) === $projectNs.'\\viewrouter') {
                    $path = dirname(__DIR__, 3).'/simulator/'.str_replace('\\', $ds, $GLOBALS['project']).'/'.$prefix.'.php';
                }


                if (strtolower($class) === $projectNs.'\\jobqueue') {
                    $path = dirname(__DIR__, 3).'/simulator/'.str_replace('\\', $ds, $GLOBALS['project']).'/'.$prefix.'.php';
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
