<?php
/**
 * Data Record class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\ObjectType;

require_once dirname(__FILE__, 2).'/ObjectTrait.inc';

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Storage\StorageFactory;
use \PerspectiveSimulator\Objects\ObjectTrait as ObjectTrait;

/**
 * DataRecord Class
 */
class DataRecord
{

    use ObjectTrait;


    /**
     * Construct function for Data Record.
     *
     * @param object $store   The store the data record belongs to.
     * @param string $id      The id of the data record.
     *
     * @return void
     */
    final public function __construct(\PerspectiveSimulator\StorageType\DataStore $store, string $id)
    {
        $this->store = $store;
        $this->id    = $id;

        if ($this->load() === false) {
            $this->save();
        }

    }//end __construct()

    /**
     * Gets a data record property value.
     *
     * @param string $propertyCode The property to get the value of.
     *
     * @return mixed
     * @throws \Exception When the propertyCode doesn't exist.
     */
    final public function getValue(string $propertyCode)
    {
        $prop = StorageFactory::getDataRecordProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if (isset($this->properties[$propertyCode]) === true) {
            return $this->properties[$propertyCode];
        }

        return $prop['default'];

    }//end getValue()


    /**
     * Sets the data record property value.
     *
     * @param string $propertyCode The property to set the value of.
     * @param mixed  $value        The property value to set.
     *
     * @return void
     * @throws \Exception When the propertyCode doesn't exist or the value isn't unique.
     */
    final public function setValue(string $propertyCode, $value)
    {
        $prop = StorageFactory::getDataRecordProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if ($prop['type'] === 'unique') {
            $current = $this->store->getUniqueDataRecord($propertyCode, $value);
            if ($current !== null) {
                throw new \Exception('Unique value "'.$value.'" is already in use');
            }

            $this->store->setUniqueDataRecord($propertyCode, $value, $this);
        }

        $this->properties[$propertyCode] = $value;

        $this->save();

    }//end setValue()


    /**
     * Deletes a data record property value.
     *
     * @param string $propertyCode The property to get the value of.
     *
     * @return mixed
     * @throws \Exception When the propertyCode doesn't exist.
     */
    final public function deleteValue(string $propertyCode)
    {
        if (isset($this->properties[$propertyCode]) === true) {
            unset($this->properties[$propertyCode]);
            $this->save();
        }

    }//end deleteValue()


    /**
     * Gets the list of children for the data record.
     *
     * @param integer $depth How many levels of children should be returned. For example, a depth of 1 will only return
     *                       direct children of the given data record, while a depth of 2 will return direct children
     *                       and their children as well. If NULL, all data records under the current data record will be
     *                       returned regardless of depth.
     *
     * @return array
     */
    final public function getChildren(int $depth=null)
    {
        return $this->store->getChildren($this->id, $depth);

    }//end getChildren()


    /**
     * Gets the list of parents for the data record.
     *
     * @param integer $depth How many levels of parents should be returned. For example, a depth of 1 will only return
     *                       direct parent of the given data record, while a depth of 2 will return direct parent
     *                       and their parent as well. If NULL, all data records under the current data record will be
     *                       returned regardless of depth.
     *
     * @return array
     */
    final public function getParents(int $depth=null)
    {
        return $this->store->getParents($this->id, $depth);

    }//end getChildren()


}//end class
