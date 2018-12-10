<?php
/**
 * Property base class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\PropertyType;


/**
 * Property Class.
 */
abstract class Property
{

    /**
     * ID of the property object.
     *
     * @var string
     */
    protected $id = null;

    /**
     * ID of the property.
     *
     * @var integer
     */
    protected $propertyid = null;

    /**
     * The property system type.
     *
     * @var string
     */
    protected $systemType = null;

    /**
     * The object (page, user etc) or NULL for no object context.
     *
     * Also private so that we can throw an error when its used out of context.
     * E.g calling DataStore->property('recordset')->getRecords() is out of context.
     * E.g calling DataStore->getDataRecord->property('recordset')->getRecords() is in context.
     *
     * @var object
     * @see $this->getObject()
     * @see $this->getObjectid()
     */
    private $object = null;


    /**
     * Class Constructor.
     *
     * @param object  $owner        The owner object (storage type or object type).
     * @param string  $propertyCode The property code.
     * @param string  $systemType   The property system type.
     *
     * @return void
     * @throws \Exception When invalid owner.
     */
    final public function __construct($owner, string $propertyCode, string $systemType)
    {
        // We need to validate the owner instances before calling validateConstructor.
        if ($owner instanceof \PerspectiveSimulator\StorageType\UserStore
            || $owner instanceof \PerspectiveSimulator\StorageType\DataStore
        ) {
            $this->object = null;
        } else if ($owner instanceof \PerspectiveSimulator\ObjectType\User
            || $owner instanceof \PerspectiveSimulator\ObjectType\DataRecord
            || $owner instanceof \PerspectiveSimulator\ObjectType\Deployment
        ) {
            $this->object = $owner;
        } else {
            throw new \Exception(_('Invalid owner object in property constructor'));
        }

        $this->id         = $propertyCode;
        $this->systemType = $systemType;

        $this->construct();

    }//end __construct()


    /**
     * Constructor.
     *
     * Opportunity to intialise any custom object vars since __construct is final.
     *
     * @return void
     */
    public function construct()
    {

    }//end construct()


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


}//end class
