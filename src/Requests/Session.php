<?php
/**
 * Request class for Perspective Simulator.
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
 * Request Class
 */
class Session
{


    /**
     * Loads the session.
     *
     * @return boolean
     */
    public static function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            $_SESSION = [];
            return false;
        }

        $filePath = Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['projectPath'].'/session.json';
        if (file_exists($filePath) === true) {
            $_SESSION = Libs\Util::jsonDecode(file_get_contents($filePath));
        } else {
            $_SESSION = [];
        }

        return true;

    }//end load()


    /**
     * Saves the session.
     *
     * @return boolean
     */
    public static function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $filePath = Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['projectPath'].'/session.json';
        file_put_contents($filePath, Libs\Util::jsonEncode($_SESSION));

        return true;

    }//end load()


    /**
     * Gets the session data for a key
     *
     * @param string $key The key we want the data from.
     *
     * @return mixed
     */
    public static function getValue(string $key)
    {
        if (isset($_SESSION[$key]) === false) {
            return null;
        }

        return $_SESSION[$key];

    }//end getvalue()


    /**
     * Sests the session data for a key
     *
     * @param string $key   The key we want to store the data against.
     * @param mixed  $value The data we want to store in the session.
     *
     * @return void
     */
    public static function setValue(string $key, $value)
    {
        $_SESSION[$key] = $value;
        self::save();

    }//end setvalue()


}//end class
