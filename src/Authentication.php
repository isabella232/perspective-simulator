<?php
/**
 * Authentication class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

use PerspectiveSimulator\Storage\StorageFactory;

/**
 * Authentication class.
 */
class Authentication
{

    /**
     * The current user.
     *
     * @var object
     */
    private static $user = null;

    /**
     * Logged in flag.
     *
     * @var boolean
     */
    private static $loggedIn = false;

    /**
     * Secret Key.
     *
     * @var string
     */
    private static $secretKey = null;


    /**
     * Gets the current user object.
     *
     * @return object|null
     */
    final public static function getCurrentUser()
    {
        $user = self::getCurrentUserid();
        if ($user === null) {
            return null;
        }

        return self::$user;

    }//end getCurrentUser()


    /**
     * Gets current userid.
     *
     * @return object|null
     */
    final public static function getCurrentUserid()
    {
        if (self::$user === null) {
            return null;
        }

        return self::$user->getId();

    }//end getCurrentUserid()


    /**
     * Login.
     *
     * @param object $user The user we want to login.
     *
     * @return void
     */
    final public static function login(\PerspectiveSimulator\RecordType\User $user)
    {
        self::$user     = $user;
        self::$loggedIn = true;

    }//end login()


    /**
     * Login.
     *
     * @return boolean
     */
    final public static function isLoggedIn()
    {
        return self::$loggedIn;

    }//end isLoggedIn()


    /**
     * Logout
     *
     * @return boolean
     */
    final public static function logoutUser()
    {
        self::$user     = null;
        self::$loggedIn = false;
        return true;

    }//end logoutUser()


    /**
     * Generates the secret key.
     *
     * @return string
     */
    public static function generateSecretKey()
    {
        // Check if the key exists in file and return that instead or generating a new one.
        if (isset($GLOBALS['project']) === true) {
            $authFile = Bootstrap::getSimulatorDir().'/'.$GLOBALS['project'].'/authentication.json';
            if (file_exists($authFile) === true) {
                $keys            = json_decode(file_get_contents($authFile), true);
                self::$secretKey = $keys['secretKey'];
                return self::$secretKey;
            }
        }

        $uid = strtoupper(md5(uniqid(random_int(0, 2147483647), true)));
        return substr($uid, 0, 32);

    }//end generateSecretKey()


    /**
     * Gets the secret key/Generates if it doesn't exist.
     *
     * @return string
     */
    public static function getSecretKey()
    {
        if (self::$secretKey === null) {
            self::$secretKey = self::generateSecretKey();
        }

        return self::$secretKey;

    }//end getSecretKey()


}//end class
