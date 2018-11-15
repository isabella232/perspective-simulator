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

/**
 * Request Class
 */
class Session
{


    /**
     * Gets the "session" data for a key
     *
     * @param string $key The key we want the data from.
     *
     * @return mixed
     */
    public static function getvalue(string $key)
    {
        if (isset($_SESSION[$key]) === false) {
            return null;
        }

        return $_SESSION[$key];

    }//end inAuthor()


    /**
     * Sests the "session" data for a key
     *
     * @param string $key   The key we want to store the data against.
     * @param mixed  $value The data we want to store in the session.
     *
     * @return void
     */
    public static function setvalue(string $key, $value)
    {
        $_SESSION[$key] = $value;

    }//end setvalue()


}//end class
