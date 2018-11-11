<?php
/**
 * User class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

use \PerspectiveSimulator\Storage\StorageFactory;

class DataRecord
{

    private $id = '';

    private $store = '';

    private $project = '';

    private $properties = [];

    private $references = [];


    final public function __construct(\PerspectiveSimulator\StorageType\DataStore $store, string $id, string $project)
    {
        $this->store   = $store;
        $this->id      = $id;
        $this->project = $project;

        if ($this->load() === false) {
            $this->save();
        }

    }//end __construct()


    final public function getId()
    {
        return $this->id;

    }//end getId()


    final public function getStorage()
    {
        return $this->store;

    }//end getStorage()


    final public function getValue(string $propertyCode)
    {
        $prop = StorageFactory::getDataRecordProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception("Property \"$propertyCode\" does not exist");
        }

        if (isset($this->properties[$propertyCode]) === true) {
            return $this->properties[$propertyCode];
        }

        return $prop['default'];

    }//end getValue()


    final public function setValue(string $propertyCode, $value)
    {
        $prop = StorageFactory::getDataRecordProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception("Property \"$propertyCode\" does not exist");
        }

        if ($prop['type'] === 'unique') {
            $current = $this->store->getUniqueDataRecord($propertyCode, $value);
            if ($current !== null) {
                throw new \Exception("Unique value \"$value\" is already in use");
            }

            $this->store->setUniqueDataRecord($propertyCode, $value, $this);
        }

        $this->properties[$propertyCode] = $value;

        $this->save();

    }//end setValue()


    final public function getChildren($depth=null)
    {
        return $this->store->getChildren($this->id, $depth);

    }//end getChildren()


    final public function getReference(string $code)
    {
        // TODO: check reference is valid
        if (isset($this->references[$code]) === false) {
            return null;
        }

        $ids = array_keys($this->references[$code]);

        if (count($ids) === 1) {
            return $ids[0];
        } else {
            return $ids;
        }

    }//end getReference()


    final public function addReference(string $code, $objects)
    {
        // TODO: check reference is valid
        // TODO: set reference on other side
        // TODO: save to cache
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        if (isset($this->references[$code]) === false) {
            $this->references[$code] = [];
        }

        foreach ($objects as $object) {
            $id = $object->getId();
            $this->references[$code][$id] = true;
        }

    }//end addReference()


    final public function setReference(string $code, $objects)
    {
        // TODO: check reference is valid
        // TODO: set reference on other side
        // TODO: save to cache
        if (isset($this->references[$code]) === false) {
            $this->references[$code] = [];
        }

        $this->addReference($code, $objects);

    }//end setReference()


    final public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $record = [
            'id'         => $this->id,
            'type'       => get_class($this),
            'properties' => $this->properties,
        ];

        $storeCode  = $this->store->getCode();
        $storageDir = Bootstrap::getStorageDir($this->project);
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';

        file_put_contents($filePath, json_encode($record));
        return true;

    }//end save()


    final public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $storeCode  = $this->store->getCode();
        $storageDir = Bootstrap::getStorageDir($this->project);
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';
        if (is_file($filePath) === false) {
            return false;
        }

        $data             = json_decode(file_get_contents($filePath), true);
        $this->properties = $data['properties'];
        return true;

    }//end load()


}//end class
