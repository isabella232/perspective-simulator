<?php
/**
 * AspectedObjectWriteTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

use \PerspectiveSimulator\Storage;

/**
 * AspectedObjectWriteTrait Trait.
 */
trait AspectedObjectWriteTrait
{

    /**
     * Sets the value of a given property for a given record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being set on.
     * @param mixed  $value        The value to set into the property.
     *
     * @return void
     * @throws \Exception When the propertyCode doesn't exist or the value isn't unique.
     */
    final public function setValue(string $propertyCode, $value)
    {
        $typeName = Storage\StorageManager::getStoreType($this);
        if ($typeName === null) {
            throw new \Exception('Invalid property type');
        }

        $functionName = 'get'.$typeName.'Property';
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $propertyCode);
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
     * Sets the shadow value of a given property for a given data record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being set on.
     * @param string $shadowid     The shadow ID to associate with the value.
     * @param mixed  $value        The value to set into the property. The data type of the value must match the expected
     *                             data type of the property.
     * @param array  $aspect       The aspect defines a specific variation of the property value to set.
     *
     * @return void
     * @throws InvalidDataException Thrown when propertyid is unknown.
     * @throws ReadOnlyException    When request is in read only mode.
     */
    final public function setShadowValue(string $propertyCode, string $shadowid, $value)
    {
        // TODO: implement this.

    }//end setShadowValue()


    /**
     * Deletes the set value of a given property for a given data record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being deleted from.
     *
     * @return void
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
     * Deletes the shadow value of a given property for a given data record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being deleted from.
     * @param string $shadowid     The shadow ID associated with the value.
     *
     * @return void
     * @throws InvalidDataException When the propertyid is not known.
     * @throws ReadOnlyException    When request is in read only mode.
     */
    final public function deleteShadowValue(string $propertyCode, string $shadowid)
    {
        // TODO: implement this.

    }//end deleteShadowValue()



}//end class
