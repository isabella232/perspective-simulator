<?php
/**
 * CDN class for Perspective Simulator.
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
 * CDN Class
 */
class CDN
{


    /**
     * Serves a CDN file.
     *
     * @param string $path The file path we are wanting the serve.
     *
     * @return void
     */
    public static function serveFile(string $path)
    {
        $cdnDir   = Libs\FileSystem::getCDNDir();
        $filePath = $cdnDir.'/'.urldecode($path);
        if (is_dir($filePath) === true) {
            return;
        }

        if (file_exists($filePath) === false) {
            Libs\Web::send404();
        }

        Libs\FileSystem::serveFile($filePath);

        return $filePath;

    }//end serveFile()


}//end class
