<?php
/**
 * FileSystem class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Bootstrap;

/**
 * FileSystem class
 */
class FileSystem
{


    /**
     * Gets the export directory.
     *
     * @return string
     */
    public static function getExportDir()
    {
        return dirname(__DIR__, 5);

    }//end getExportDir()


    /**
     * Gets the storage directory.
     *
     * @return string
     */
    public static function getSimulatorDir()
    {
        return self::getExportDir().'/simulator';

    }//end getSimulatorDir()


    /**
     * Gets the storage directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getStorageDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        if (Bootstrap::isReadEnabled() === false && Bootstrap::isWriteEnabled() === false) {
            return null;
        }

        return self::getSimulatorDir().'/'.$project.'/storage';

    }//end getStorageDir()


    /**
     * Gets the project directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getProjectDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        return self::getExportDir().'/projects/'.$project;

    }//end getProjectDir()


}//end class
