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

use PerspectiveSimulator\Requests\Session;
use PerspectiveSimulator\Storage\StorageFactory;
use PerspectiveSimulator\Libs;

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
        if (self::$user === null && isset($_SESSION['user']) === false) {
            return null;
        } else if (self::$user === null) {
            if (isset($_SESSION['user']) === true) {
                $store = StorageFactory::getUserStore($_SESSION['userStore']);
                if ($store !== null) {
                    self::$user = $store->getUser($_SESSION['user']);
                }
            }

            if (self::$user === null) {
                return null;
            }
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
    final public static function login(\PerspectiveSimulator\ObjectType\User $user)
    {
        self::$user            = $user;
        self::$loggedIn        = true;
        Session::setValue('user', $user->getId());
        Session::setValue('userStore', $user->getStorage()->getCode());

    }//end login()


    /**
     * Checks if the user is logged in.
     *
     * @return boolean
     */
    final public static function isLoggedIn()
    {
        if (self::$loggedIn === false && isset($_SESSION['user']) === true) {
            // User is loggedIn so we can reset the flag.
            self::$loggedIn = true;
        }

        return self::$loggedIn;

    }//end isLoggedIn()


    /**
     * Logout
     *
     * @return boolean
     */
    final public static function logout()
    {
        self::$user     = null;
        self::$loggedIn = false;

        unset($_SESSION['user']);
        unset($_SESSION['userStore']);
        unset($_SESSION['moderator']);
        Session::save();

        return true;

    }//end logout()


    /**
     * Generates the secret key.
     *
     * @return string
     */
    public static function generateSecretKey()
    {
        $simulatorDir = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir();

        // Check if the key exists in file and return that instead or generating a new one.
        $authFile = $simulatorDir.'/'.$GLOBALS['project'].'/authentication.json';
        if (Bootstrap::isReadEnabled() === true && file_exists($authFile) === true) {
            $keys            = Libs\Util::jsonDecode(file_get_contents($authFile));
            self::$secretKey = $keys['secretKey'];
            return self::$secretKey;
        }

        $uid        = strtoupper(md5(uniqid(random_int(0, 2147483647), true)));
        $projectKey = substr($uid, 0, 32);

        if (Bootstrap::isWriteEnabled() === true) {
            file_put_contents(
                $simulatorDir.'/'.$GLOBALS['project'].'/authentication.json',
                Libs\Util::jsonEncode(['secretKey' => $projectKey])
            );
        }

        self::$secretKey = $projectKey;

        return $projectKey;

    }//end generateSecretKey()


    /**
     * Gets the secret key.
     *
     * @return string | null
     */
    public static function getSecretKey()
    {
        if (self::$secretKey === null) {
            if (Bootstrap::isReadEnabled() === true ) {
                $simulatorDir = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir();
                $authFile     = $simulatorDir.'/'.$GLOBALS['project'].'/authentication.json';
                if (file_exists($authFile) === true) {
                    $keys            = Libs\Util::jsonDecode(file_get_contents($authFile));
                    self::$secretKey = $keys['secretKey'];
                    return self::$secretKey;
                }
            }

            return null;
        }

        return self::$secretKey;

    }//end getSecretKey()


}//end class
