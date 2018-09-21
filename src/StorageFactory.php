<?php
namespace PerspectiveSimulator;

class StorageFactory
{
    private static $stores = [];
    private static $props = [
        'page' => [],
        'data' => [],
        'user' => [],
    ];

    public static function createDataStore(string $code)
    {
        if (isset(self::$stores['data'][$code]) === false) {
            self::$stores['data'][$code] = new DataStore($code);
        }
    }

    public static function createDataRecordProperty(string $code, string $type, $default=null)
    {
        self::$props['data'][$code] = [
            'type'    => $type,
            'default' => $default,
        ];
    }

    public static function getDataRecordProperty(string $code)
    {
        return self::$props['data'][$code] ?? null;
    }







    public static function getDataStore(string $code)
    {
        if (isset(self::$stores['data'][$code]) === false) {
            throw new \Exception("Data store \"$code\" does not exist");
        }

        return self::$stores['data'][$code];
    }
}
