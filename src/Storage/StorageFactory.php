<?php
/**
 * StorageFactory class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Storage;

use \PerspectiveAPI\Storage\StorageFactory as PerspectiveAPIStorageFactory;

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\StorageType\DataStore;
use \PerspectiveSimulator\StorageType\UserStore;

/**
 * StorageFactory Class.
 */
class StorageFactory extends PerspectiveAPIStorageFactory
{

    /**
     * Array of stores.
     *
     * @var array
     */
    private static $stores = [];

    /**
     * Array of props.
     *
     * @var array
     */
    private static $props = [
        'page'    => [],
        'data'    => [],
        'user'    => [],
        'project' => [],
    ];


    /**
     * Creates a new Data Store.
     *
     * @param string $name The name of the user store.
     *
     * @return void
     */
    public static function createDataStore(string $name, string $project)
    {
        $project = self::getProjectPrefix();
        if (isset(self::$stores['data'][$project]) === false) {
            self::$stores['data'][$project] = [];
        }

        if (isset(self::$stores['data'][$project][$name]) === false) {
            self::$stores['data'][$project][$name] = new DataStore($name);
        }

    }//end createDataStore()


    /**
     * Creates a new User Store.
     *
     * @param string $name The name of the user store.
     *
     * @return void
     */
    public static function createUserStore(string $name, string $project)
    {
        $project = self::getProjectPrefix();
        if (isset(self::$stores['user'][$project]) === false) {
            self::$stores['user'][$project] = [];
        }

        if (isset(self::$stores['user'][$name]) === false) {
            self::$stores['user'][$project][$name] = new UserStore($name);
        }

    }//end createUserStore()


    /**
     * Creates a data record property
     *
     * @param string $code    The property code.
     * @param string $type    The type of data record property.
     * @param mixed  $default The default value.
     *
     * @return void
     */
    public static function createDataRecordProperty(string $code, string $type, $default=null)
    {
        self::$props['data'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];

    }//end createDataRecordProperty()


    /**
     * Creates a user property
     *
     * @param string $code    The property code.
     * @param string $type    The type of user property.
     * @param mixed  $default The default value.
     *
     * @return void
     */
    public static function createUserProperty(string $code, string $type, $default=null)
    {
        self::$props['user'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];

    }//end createUserProperty()


    /**
     * Creates a deployment (project) property
     *
     * @param string $code    The property code.
     * @param string $type    The type of user property.
     * @param mixed  $default The default value.
     *
     * @return void
     */
    public static function createProjectProperty(string $code, string $type, $default=null)
    {
        self::$props['project'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];

    }//end createDeployementProperty()


    /**
     * Retrieves a data record property.
     *
     * @param string $code The code of the data record property.
     *
     * @return mixed.
     */
    public static function getDataRecordProperty(string $code)
    {
        $project = self::getProjectPrefix();
        if (strpos($code, $project) === false) {
            $code = $project.'-'.$code;
        }

        return (self::$props['data'][$code] ?? null);

    }//end getDataRecordProperty()


    /**
     * Retrieves a user property.
     *
     * @param string $code The code of the user property.
     *
     * @return mixed.
     */
    public static function getUserProperty(string $code)
    {
        $project = self::getProjectPrefix();
        if (strpos($code, $project) === false) {
            $code = $project.'-'.$code;
        }

        return (self::$props['user'][$code] ?? null);

    }//end getUserProperty()


    /**
     * Retrieves a user property.
     *
     * @param string $code The code of the user property.
     *
     * @return mixed.
     */
    public static function getProjectProperty(string $code)
    {
        $project = self::getProjectPrefix();
        if (strpos($code, $project) === false) {
            $code = $project.'-'.$code;
        }

        return (self::$props['project'][$code] ?? null);

    }//end getProjectProperty()


    /**
     * Retrieves a named data store.
     *
     * @param string $name The name of the data store.
     *
     * @return object
     * @throws \Exception When data store doesn't exist.
     */
    public static function getDataStore(string $name)
    {
        $project = self::getProjectPrefix();
        if (isset(self::$stores['data'][$project][$name]) === false) {
            throw new \Exception('Data store "'.$name.'" does not exist');
        }

        return self::$stores['data'][$project][$name];

    }//end getDataStore()


    /**
     * Retrieves a named user store.
     *
     * @param string $name The name of the user store.
     *
     * @return object
     * @throws \Exception When user store doesn't exist.
     */
    public static function getUserStore(string $name)
    {
        $project = self::getProjectPrefix();
        if (isset(self::$stores['user'][$project][$name]) === false) {
            throw new \Exception('User store "'.$name.'" does not exist');
        }

        return self::$stores['user'][$project][$name];

    }//end getUserStore()


    public static function getProjectPrefix()
    {
        $bt = debug_backtrace(false);

        // Remove the call to this and the call to the function that needs the property code prefixed.
        array_shift($bt);
        array_shift($bt);

        $key = 0;
        foreach ($bt as $id => $call) {
            if ($call['function'] === 'eval') {
                $key = ($id + 1);
                break;
            }
        }

        $called = $bt[$key];
        if (isset($called['class']) === true && strpos(strtolower($GLOBALS['project']), strtolower($called['class'])) !== false) {
            $classParts   = explode('\\', $called['class']);
            return Bootstrap::generatePrefix($classParts[0].'\\'.$classParts[1]);
        } else {
            return Bootstrap::generatePrefix($GLOBALS['project']);
        }

    }//end getProjectPrefix()


}//end class
