<?php
/**
 * User class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace Perspective\PHPClass\ObjectType;

require_once dirname(__FILE__, 2).'/ObjectTrait.inc';
require_once dirname(__FILE__, 2).'/ReferenceObjectTrait.inc';
require_once dirname(__FILE__, 2).'/ObjectInterface.inc';

use \PerspectiveSimulator\Storage;
use \PerspectiveSimulator\Objects\ObjectTrait as ObjectTrait;
use \PerspectiveSimulator\Objects\ReferenceObjectTrait as ReferenceObjectTrait;
use \PerspectiveSimulator\Objects\ObjectInterface as ObjectInterface;

/**
 * User class.
 */
class User implements ObjectInterface
{

    use ObjectTrait;

    use ReferenceObjectTrait;

    /**
     * The username of the user.
     *
     * @var string
     */
    private $username = '';

    /**
     * The groups of the user.
     *
     * @var array
     */
    private $groups = [];


    /**
     * Creates a new user record in the user store.
     *
     * @param object $store     The store the user record belongs to.
     * @param string $id        The id of the record.
     * @param string $username  The user name of the user.
     * @param string $firstName The users first name.
     * @param string $lastName  The users last name.
     *
     * @return object|null
     */
    final public function __construct(
        \PerspectiveSimulator\StorageType\UserStore $store,
        string $id,
        string $username,
        string $firstName,
        string $lastName
    ) {
        $this->store    = $store;
        $this->id       = $id;
        $this->username = $username;

        if ($this->load() === false) {
            $this->setFirstName($firstName);
            $this->setLastName($lastName);

            \PerspectiveSimulator\Bootstrap::queueSave($this);
        }

    }//end __construct()


    /**
     * Gets a users property value.
     *
     * @param string $propertyCode The property to get the value of.
     *
     * @return mixed
     * @throws \Exception When the propertyCode doesn't exist.
     */
    final public function getValue(string $propertyCode)
    {
        $typeName = Storage\StorageManager::getStoreType($this);
        if ($typeName === null) {
            throw new \Exception('Invalid property type');
        }

        $unprefixedCode = $propertyCode;
        $propertyCode   = $this->getPrefixedPropertyCode($propertyCode);

        $functionName = 'get'.$typeName.'Property';
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $unprefixedCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        return $this->properties[$propertyCode];

    }//end getValue()


    /**
     * Sets the users property value.
     *
     * @param string $propertyCode The property to set the value of.
     * @param mixed  $value        The property value to set.
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

        $unprefixedCode = $propertyCode;
        $propertyCode   = $this->getPrefixedPropertyCode($propertyCode);

        $functionName = 'get'.$typeName.'Property';
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $unprefixedCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if ($prop['type'] === 'unique') {
            $current = $this->store->getUniqueUserRecord($unprefixedCode, $value);
            if ($current !== null) {
                throw new \Exception('Unique value "'.$value.'" is already in use');
            }

            $this->store->setUniqueDataRecord($unprefixedCode, $value, $this);
        }

        $this->properties[$propertyCode] = $value;

        \PerspectiveSimulator\Bootstrap::queueSave($this);

    }//end setValue()


    /**
     * Sets the users first name.
     *
     * @param string $firstName The users first name.
     *
     * @return void
     */
    final public function setFirstName(string $firstName)
    {
        $this->setValue('__first-name__', $firstName);

    }//end setFirstName()


    /**
     * Sets the users last name.
     *
     * @param string $lastName The users last name.
     *
     * @return void
     */
    final public function setLastName(string $lastName)
    {
        $this->setValue('__last-name__', $lastName);

    }//end setLastName()


    /**
     * Sets the users first name.
     *
     * @return string
     */
    final public function getFirstName()
    {
        return $this->getValue('__first-name__');

    }//end getFirstName()


    /**
     * Sets the users first name.
     *
     * @return string
     */
    final public function getLastName()
    {
        return $this->getValue('__last-name__');

    }//end getLastName()


    /**
     * Sets the users first name.
     *
     * @return string
     */
    final public function getUsername()
    {
        return $this->username;

    }//end getUsername()


    /**
     * Assign an user to parent groups.
     *
     * @param mixed $groupid Parent user groups to assign the user to.
     *
     * @return void
     * @throws ReadOnlyException When request is in read only mode.
     */
    final public function addToGroup($groupid)
    {
        if (isset($this->groups[$groupid]) === false) {
            $this->groups[$groupid] = true;
        }

    }//end addToGroup()


    /**
     * Remove an user from specified parent groups.
     *
     * @param mixed $groupid Parent user groups to remove the user from.
     *
     * @return void
     * @throws ReadOnlyException When request is in read only mode.
     */
    final public function removeFromGroup($groupid)
    {
        if (isset($this->groups[$groupid]) === true) {
            unset($this->groups[$groupid]);
        }

    }//end removeFromGroup()


    /**
     * Returns all parent group entityids for a specified user.
     *
     * @return array
     */
    final public function getGroups()
    {
        return array_keys($this->groups);

    }//end getGroups()


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


}//end class
