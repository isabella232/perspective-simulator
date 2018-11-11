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

    private static $stores = [];

    private static $props = [
        'page' => [],
        'data' => [],
        'user' => [],
    ];


    public static function createDataStore(string $code, string $project)
    {
        if (isset(self::$stores['data'][$code]) === false) {
            self::$stores['data'][$code] = new DataStore($code, $project);
        }

    }//end createDataStore()


    public static function createUserStore(string $code, string $project)
    {
        if (isset(self::$stores['user'][$code]) === false) {
            self::$stores['user'][$code] = new UserStore($code, $project);
        }

    }//end createUserStore()


    public static function createDataRecordProperty(string $code, string $type, $default=null)
    {
        self::$props['data'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];

    }//end createDataRecordProperty()


    public static function createUserProperty(string $code, string $type, $default=null)
    {
        self::$props['user'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];

    }//end createUserProperty()


    public static function getDataRecordProperty(string $code)
    {
        return self::$props['data'][$code] ?? null;

    }//end getDataRecordProperty()


    public static function getUserProperty(string $code)
    {
        return self::$props['user'][$code] ?? null;

    }//end getUserProperty()


    public static function getDataStore(string $code)
    {
        if (isset(self::$stores['data'][$code]) === false) {
            throw new \Exception("Data store \"$code\" does not exist");
        }

        return self::$stores['data'][$code];

    }//end getDataStore()


    public static function getUserStore(string $code)
    {
        if (isset(self::$stores['user'][$code]) === false) {
            throw new \Exception("User store \"$code\" does not exist");
        }

        return self::$stores['user'][$code];

    }//end getUserStore()


}//end class
