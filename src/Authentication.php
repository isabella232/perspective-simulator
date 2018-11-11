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

/**
 * Authentication class.
 */
class Authentication
{

    /**
     * The current userid.
     *
     * @var string
     */
    private static $userid = null;

    /**
     * Logged in flag.
     *
     * @var boolean
     */
    private static $loggedIn = false;


    /**
     * Gets the current user object.
     *
     * @return object|null
     */
    final public static function getCurrentUser()
    {
        $userid = self::getCurrentUserid();
        if ($userid === null) {
            return null;
        }

        $userStore = StorageFactory::getUserStore($userid);
        if (empty($userStore) === true) {
            return null;
        }

        $userStoreObject = StorageFactory::getUserStore($userStore['code']);
        if ($userStoreObject === null) {
            return null;
        }

        $userObject = $userStoreObject->getUser($userid);
        return $userObject;

    }//end getCurrentUser()


    /**
     * Gets current userid.
     *
     * @return object|null
     */
    final public static function getCurrentUserid()
    {
        return self::$userid;

    }//end getCurrentUserid()


    /**
     * Login.
     *
     * @param object $user The user we want to login.
     *
     * @return void
     */
    final public static function login(\PerspectiveSimulator\User $user)
    {
        self::$userid   = $user->getId();
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
        self::$userid   = null;
        self::$loggedIn = false;
        return true;

    }//end logoutUser()


}//end class
