<?php
namespace PerspectiveSimulator;

class DataRecord
{

    private $id = '';
    private $store = '';
    private $props = [];


    final public function __construct(DataStore $store, string $id) {
        $this->store = $store;
        $this->id = $id;
    }


    final public function getId()
    {
        return $this->id;
    }

    final public function getStorage()
    {
        return $this->store;
    }


    final public function getValue(string $propertyCode)
    {
        $prop = StorageFactory::getDataRecordProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception("Property \"$propertyCode\" does not exist");
        }

        return $this->props[$propertyCode];
    }


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

        $this->props[$propertyCode] = $value;
    }

    final public function getChildren($depth=null)
    {
        return $this->store->getChildren($this->id, $depth);

    }


}//end class
