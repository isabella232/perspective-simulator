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

use \PerspectiveSimulator\StorageType\DataStore;
use \PerspectiveSimulator\StorageType\UserStore;

/**
 * StorageFactory Class.
 */
class StorageFactory
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
    public static function createDataStore(string $name)
    {
        if (isset(self::$stores['data'][$name]) === false) {
            self::$stores['data'][$name] = new DataStore($name);
        }

    }//end createDataStore()


    /**
     * Creates a new User Store.
     *
     * @param string $name The name of the user store.
     *
     * @return void
     */
    public static function createUserStore(string $name)
    {
        if (isset(self::$stores['user'][$name]) === false) {
            self::$stores['user'][$name] = new UserStore($name);
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
    public static function createDeployementProperty(string $code, string $type, $default=null)
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
        return (self::$props['user'][$code] ?? null);

    }//end getUserProperty()


    /**
     * Retrieves a user property.
     *
     * @param string $code The code of the user property.
     *
     * @return mixed.
     */
    public static function getDeploymentProperty(string $code)
    {
        return (self::$props['project'][$code] ?? null);

    }//end getDeploymentProperty()


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
        if (isset(self::$stores['data'][$name]) === false) {
            throw new \Exception('Data store "'.$name.'" does not exist');
        }

        return self::$stores['data'][$name];

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
        if (isset(self::$stores['user'][$name]) === false) {
            throw new \Exception('User store "'.$name.'" does not exist');
        }

        return self::$stores['user'][$name];

    }//end getUserStore()


}//end class
