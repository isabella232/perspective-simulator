<?php
/**
 * User class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\ObjectType;

require_once dirname(__FILE__, 2).'/ObjectTrait.inc';
require_once dirname(__FILE__, 2).'/ReferenceObjectTrait.inc';

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\Storage\StorageFactory;
use \PerspectiveSimulator\Objects\ObjectTrait as ObjectTrait;
use \PerspectiveSimulator\Objects\ReferenceObjectTrait as ReferenceObjectTrait;

/**
 * User class.
 */
class User
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

            $this->save();
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
        $prop = StorageFactory::getUserProperty($propertyCode);
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
        $prop = StorageFactory::getUserProperty($propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if ($prop['type'] === 'unique') {
            $current = $this->store->getUniqueUserRecord($propertyCode, $value);
            if ($current !== null) {
                throw new \Exception('Unique value "'.$value.'" is already in use');
            }

            $this->store->setUniqueDataRecord($propertyCode, $value, $this);
        }

        $this->properties[$propertyCode] = $value;

        $this->save();

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
     * Save Data Record to file for cache.
     *
     * @return boolean
     */
    public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $record = [
            'id'         => $this->id,
            'type'       => get_class($this),
            'properties' => $this->properties,
            'references' => $this->references,
            'groups'     => $this->groups,
            'username'   => $this->username,
        ];

        $storeCode  = $this->store->getCode();
        $storageDir = Bootstrap::getStorageDir();
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';

        file_put_contents($filePath, Libs\Util::jsonEncode($record));
        return true;

    }//end save()


    /**
     * Load Data Record to file for cache.
     *
     * @return boolean
     */
    public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $storeCode  = $this->store->getCode();
        $storageDir = Bootstrap::getStorageDir();
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';
        if (is_file($filePath) === false) {
            return false;
        }

        $data             = Libs\Util::jsonDecode(file_get_contents($filePath));
        $this->properties = $data['properties'];
        $this->references = $data['references'];
        $this->groups     = $data['groups'];
        $this->username   = $data['username'];
        return true;

    }//end load()


}//end class
