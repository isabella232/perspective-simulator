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
        $path = dirname(__FILE__).'/Web/'.urldecode($path);
        if (file_exists($path) === true) {
            // Path is a file so lets just serve it.
            self::serveFile($path);
        } else if (file_exists($path.'.php') === true) {
            // Path is a UI view so lets paint it now.
            ob_start();
            include $path.'.php';
            $contents = trim(ob_get_contents());
            ob_end_clean();
            echo $contents;
        } else {
            // Nothing found.
            Libs\Web::send404();
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
