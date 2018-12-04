<?php
/**
 * UI class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\UI;

use \PerspectiveSimulator\Libs;

/**
 * UI Class
 */
class UI
{


    /**
     * Paint Simulator UI.
     *
     * @param  string $path The path from the router.
     *
     * @return void.
     */
    public static function paint(string $path)
    {
        if ($path === '') {
            $indexPaths = ['index.html', 'index.php'];
            foreach ($indexPaths as $path) {
                $path = dirname(__FILE__).'/Web/'.urldecode($path);
                if (file_exists($path) === true) {
                    break;
                }
            }
        } else {
            $path = dirname(__FILE__).'/Web/'.urldecode($path);
        }

        if (file_exists($path) === false) {
            Libs\Web::send404();
        }

        $ext = Libs\FileSystem::getExtension($path);
        if ($ext === 'php') {
            ob_start();
            include $path;
            $contents = trim(ob_get_contents());
            ob_end_clean();
            echo $contents;
        } else {
            self::serveFile($path);
        }

    }//end paint()


    /**
     * Serves a UI file.
     *
     * @param string $path The file path we are wanting the serve.
     *
     * @return void
     */
    private static function serveFile(string $path)
    {
        Libs\FileSystem::serveFile($path);

    }//end serveFile()


}//end class
