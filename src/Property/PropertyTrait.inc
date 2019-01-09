<?php
/**
 * NumberTrait for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Property;

/**
 * PropertyTrait Trait.
 */
trait PropertyTrait
{

    /**
     * The property system type.
     *
     * @var string
     */
    private $systemType = null;

    /**
     * ID of the property.
     *
     * @var integer
     */
    private $propertyid = null;


    /**
     * Sets the type of the property system.
     *
     * @return void
     */
    public function setSystemType(string $systemType)
    {
        $this->systemType = $systemType;

    }//end setSystemType()


    /**
     * Gets the value of the property.
     *
     * @return mixed
     */
    public function getValue()
    {
        $object = $this->getObject();
        return $object->getValue($this->id);

    }//end getValue()


    /**
     * Sets the value of the property.
     *
     * @param mixed $value The value to set into the property.
     *
     * @return void
     * @throws InvalidDataException When propertyid is not known.
     * @throws ReadOnlyException    When request is in read only mode.
     */
    final public function setValue($value)
    {
        $object = $this->getObject();
        return $object->setValue($this->id, $value);

    }//end setValue()


    /**
     * Deletes the set value of the property.
     *
     * @return void
     * @throws \Exception Thrown when propertyid is unknown.
     */
    final public function deleteValue()
    {
        $object = $this->getObject();
        return $object->deleteValue($this->id);

    }//end deleteValue()


    /**
     * Get the id of the object.
     *
     * @return string
     */
    final public function getId()
    {
        return $this->id;

    }//end getId()


    /**
     * Get objectid to query propertes with.
     *
     * Call getObject() or getObjectid() functions first to validate you are in object context.
     *
     * @return string
     * @throws \Exception When invalid.
     * @see $this->object
     */
    final protected function getObject()
    {
        if ($this->object === null) {
            throw new \Exception(
                sprintf(_('Calling %s class method without object context'), get_called_class())
            );

        }
        return $this->object;

    }//end getObjectid()


    /**
     * Get objectid to query propertes with.
     *
     * Call getObject() or getObjectid() functions first to validate you are in object context.
     *
     * @return string
     * @see $this->object
     */
    final protected function getObjectid()
    {
        return $this->getObject()->getId();

    }//end getObjectid()


    /**
     * As this can't be chagned by others we can assume its valid.
     *
     * @return boolean
     */
    final protected function validateObjectid()
    {
        return true;

    }//end validateObjectid()


    /**
     * Prepare for a write operation,
     *
     * Validates a write operation.
     * Opens a DB transaction.
     * Acquires a lock.
     *
     * @return void
     */
    final protected function prepareWrite()
    {
        // TODO:: workout if we need this in simulator.

    }//end prepareWrite()


}//end trait