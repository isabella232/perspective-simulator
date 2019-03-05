<?php
/**
 * Property class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Requests;

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;

/**
 * Property Class
 */
class Property
{


    /**
     * Serves a Property file/image.
     *
     * @param string $path The file path we are wanting the serve.
     *
     * @return void
     */
    public static function serveFile(string $path)
    {
        $simDir    = Libs\FileSystem::getStorageDir().'/properties';
        $filePath  = $simDir.'/'.urldecode($path);

        if (file_exists($filePath) === false) {
            Libs\Web::send404();
        } else if (file_exists($filePath) === true) {
            // We have a value lets serve that.
            Libs\FileSystem::serveFile($filePath);
            return $filePath;
        }

        Libs\Web::send404();
        return $filePath;

    }//end serveFile()


}//end class
