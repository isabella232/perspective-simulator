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
        $pathParts = explode('/', $filePath);
        $file      = array_pop($pathParts);
        $type      = array_pop($pathParts);

        $fileParts = explode('-', $file);
        $prefix    = $fileParts[0].'\\'.$fileParts[1];
        if ($prefix === strtolower($GLOBALS['project'])) {
            array_shift($fileParts);
            array_shift($fileParts);
            $defaultPath = Libs\FileSystem::getProjectDir().'/Properties/'.$type.'/'.implode('-', $fileParts);
        } else {
            $project = $fileParts[0].'/'.$fileParts[1];
            array_shift($fileParts);
            array_shift($fileParts);
            $defaultPath = substr(Libs\FileSystem::getProjectDir(), 0, -4).'/vendor/'.$project.'/src/Properties/'.$type.'/'.implode('-', $fileParts);
        }



        if (file_exists($filePath) === false && file_exists($defaultPath) === false) {
            Libs\Web::send404();
        } else if (file_exists($filePath) === false && file_exists($defaultPath) === true) {
            // We don't have a value but we do have a default file.
            Libs\FileSystem::serveFile($defaultPath);
            return $defaultPath;
        } else if (file_exists($filePath) === true) {
            // We have a value lets serve that.
            Libs\FileSystem::serveFile($filePath);
            return $filePath;
        }

        Libs\Web::send404();
        return $filePath;

    }//end serveFile()


}//end class
