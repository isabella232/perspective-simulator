<?php
/**
 * StoreTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Record;

use \PerspectiveSimulator\Bootstrap;

/**
 * StoreTrait Trait.
 */
trait RecordTrait
{

    /**
     * The data record id.
     *
     * @var string
     */
    private $id = '';

    /**
     * The store the data record belongs to.
     *
     * @var object
     */
    private $store = '';

    /**
     * Array of data record properties attached to the record.
     *
     * @var array
     */
    private $properties = [];

    /**
     * Array of references attached to the record.
     *
     * @var array
     */
    private $references = [];


    /**
     * Gets the internal ID of the data record.
     *
     * @return string
     */
    final public function getId()
    {
        return $this->id;

    }//end getId()


    /**
     * Gets the data store that the data record is contained within.
     *
     * @return object
     */
    final public function getStorage()
    {
        return $this->store;

    }//end getStorage()


    /**
     * Gets the value of a reference.
     *
     * @param string $referenceid The ID of the reference that is being retrieved.
     *
     * @return string|array
     * @throws \Exception When reference is invalid.
     */
    final public function getReference(string $referenceid)
    {
        if ($this->validateReference($referenceid) === false) {
            throw new \Exception(_('Invalid reference "'.$referenceid.'"'));
        }

        if (isset($this->references[$referenceid]) === false) {
            return null;
        }

        $ids = array_keys($this->references[$referenceid]);

        if (count($ids) === 1) {
            return $ids[0];
        } else {
            return $ids;
        }

    }//end getReference()


    /**
     * Adds an object to an existing reference value.
     *
     * @param string $referenceid The ID of the reference that is being retrieved.
     * @param mixed  $objects     One or more objects to add to the reference, retrieved from the store that the
     *                            reference points to.
     *
     * @return void
     * @throws \Exception When reference is invalid.
     */
    final public function addReference(string $referenceid, $objects)
    {
        if ($this->validateReference($referenceid) === false) {
            throw new \Exception(_('Invalid reference "'.$referenceid.'"'));
        }

        // TODO: set reference on other side.
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        if (isset($this->references[$referenceid]) === false) {
            $this->references[$referenceid] = [];
        }

        foreach ($objects as $object) {
            $id = $object->getId();
            $this->references[$referenceid][$id] = true;
        }

        $this->save();

    }//end addReference()


    /**
     * Sets an object to an existing reference value.
     *
     * @param string $referenceid The ID of the reference that is being retrieved.
     * @param mixed  $objects     One or more objects to add to the reference, retrieved from the store that the
     *                            reference points to.
     *
     * @return void
     * @throws \Exception When reference is invalid.
     */
    final public function setReference(string $referenceid, $objects)
    {
        // Let addReference handle the save and validation.
        $this->addReference($referenceid, $objects);

    }//end setReference()


    /**
     * Removes an object from an existing reference value.
     *
     * @param string $referenceid The ID of the reference to remove objects from.
     * @param mixed  $objects     One or more objects to remove the reference, retrieved from the store that the
     *                            reference points to.
     *
     * @return void
     * @throws \Exception When reference is invalid.
     */
    final public function deleteReference(string $referenceid, $objects)
    {
        if ($this->validateReference($referenceid) === false) {
            throw new \Exception(_('Invalid reference "'.$referenceid.'"'));
        }

        // TODO: Delete reference on other side.
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        foreach ($objects as $object) {
            $id = $object->getId();
            unset($this->references[$referenceid][$id]);
        }

        if (count($this->references) === 0) {
            unset($this->references[$referenceid]);
        }

        $this->save();

    }//end deleteReference()


    /**
     * Validates if the reference can be set.
     *
     * @param string $referenceid The id of the reference we are trying to set.
     *
     * @return boolean.
     */
    final private function validateReference(string $referenceid)
    {
        $valid = false;
        $code  = $this->store->getCode();

        $storageDir = Bootstrap::getProjectDir();
        $filePath   = $storageDir.'/Stores/Data/'.$code.'/'.$referenceid.'.json';
        if (file_exists($filePath) === true) {
            $valid = true;
        }

        return $valid;

    }//end validateReference()


    /**
     * Save Data Record to file for cache.
     *
     * @return boolean
     */
    final public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $record = [
            'id'         => $this->id,
            'type'       => get_class($this),
            'properties' => $this->properties,
            'references' => $this->references,
        ];

        $storeCode  = $this->store->getCode();
        $storageDir = Bootstrap::getStorageDir();
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';

        file_put_contents($filePath, json_encode($record, JSON_PRETTY_PRINT));
        return true;

    }//end save()


    /**
     * Load Data Record to file for cache.
     *
     * @return boolean
     */
    final public function load()
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

        $data             = json_decode(file_get_contents($filePath), true);
        $this->properties = $data['properties'];
        return true;

    }//end load()


}
