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
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        if ($this->validateReference($referenceid, $objects) === false) {
            return;
            // throw new \Exception(_('Invalid reference "'.$referenceid.'"'));
        }

        if (isset($this->references[$referenceid]) === false) {
            $this->references[$referenceid] = [];
        }

        foreach ($objects as $object) {
            $id = $object->getId();
            $this->references[$referenceid][$id] = true;

            if ($object->getReference($referenceid) === null) {
                $object->addReference($referenceid, [$this]);
            }
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
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        foreach ($objects as $object) {
            $id = $object->getId();
            unset($this->references[$referenceid][$id]);

            if ($object->getReference($referenceid) !== null) {
                $object->deleteReference($referenceid, [$this]);
            }
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
     * @param array  $objects     The objects we are setting the reference against, used if we are setting the reference
     *                            for the other side.
     *
     * @return boolean.
     */
    final public function validateReference(string $referenceid, array $objects=[])
    {
        $valid     = false;
        $code      = $this->store->getCode();
        $storeType = $this->store->getType();

        $storageDir = Bootstrap::getProjectDir();
        $filePath   = $storageDir.'/Stores/'.$storeType.'/'.$code.'/'.$referenceid.'.json';
        if (file_exists($filePath) === true) {
            $reference   = json_decode(file_get_contents($filePath), true);
            $sourceValue = [];
            $targetValue = [];

            // Categorise the given objects into source and target values depending on their side in relationship.
            foreach ($objects as $object) {
                $referenceSide = $this->getReferenceSide($reference, $object);
                if ($referenceSide === 'source') {
                    $sourceValue[] = $object->getId();
                } else if ($referenceSide === 'target') {
                    $targetValue[] = $object->getId();
                }
            }

            $referenceSide = $this->getReferenceSide($reference, $this);
            if ($referenceSide === 'source') {
                $sourceValue[] = $this->getId();
            } else if ($referenceSide === 'target') {
                $targetValue[] = $this->getId();
            }

            $errorMsg = _('Expecting single %s value in %s cardinality, but %s given');
            if ($reference['cardinality'] === '1:1') {
                if (count($sourceValue) !== 1) {
                    throw new \Exception(
                        sprintf($errorMsg, 'source', $reference['cardinality'], implode(',', $sourceValue))
                    );
                }

                if (count($targetValue) !== 1) {
                    throw new \Exception(
                        sprintf($errorMsg, 'target', $reference['cardinality'], implode(',', $targetValue))
                    );
                }
            } else if ($reference['cardinality'] === '1:M') {
                if (count($sourceValue) !== 1) {
                    throw new \Exception(
                        sprintf($errorMsg, 'source', $reference['cardinality'], implode(',', $sourceValue))
                    );
                }
            }

            if ($referenceSide === 'source') {
                if ($sourceValue[0] !== $this->getId()) {
                    throw new \Exception(_('The target must be the object itself'));
                }

                $valid = true;
            } else {
                if ($targetValue[0] !== $this->getId()) {
                    throw new \Exception(_('The source must be the object itself'));
                }

                if ($reference['cardinality'] === 'M:M' && count($sourceValue) > 1) {
                    $valid = true;
                } else {
                    $valid = true;
                }
            }//end if

            $valid = true;
        }//end if

        if ($valid === false && empty($objects) === false) {
            // Must be validating the reverse reference so check the objects to see if its valid.
            foreach ($objects as $object) {
                $objectValid = $object->validateReference($referenceid);
                if ($objectValid === true) {
                    $valid = true;
                    break;
                }
            }
        }

        return $valid;

    }//end validateReference()


    /**
     * Gets the reference side (source or target) to validate the reference.
     *
     * @param array  $reference The reference data.
     * @param object $object    The object we are using to reference.
     *
     * @return string
     * @throws \Exception When invalid reference.
     */
    private function getReferenceSide(array $reference, $object=null)
    {
        if ($object === null) {
            $object = $this;
        } else if (is_object($object) === false) {
            throw new \Exception(_('Invalid referenced object: is not an object'));
        }

        if ($object instanceof \PerspectiveSimulator\RecordType\User) {
            $storageClass = 'UserStore';
        } else if ($object instanceof \PerspectiveSimulator\RecordType\DataRecord) {
            $storageClass = 'DataStore';
        } else {
            throw new \Exception(
                sprintf(_('Invalid referenced object: invalid object type: %s'), get_class($object))
            );
        }

        $storageCode = $object->getStorage()->getCode();
        if ($reference['sourceType'] === $storageClass && $reference['sourceCode'] === $storageCode) {
            return 'source';
        } else if ($reference['targetType'] === $storageClass && $reference['targetCode'] === $storageCode) {
            return 'target';
        } else {
            throw new \Exception(sprintf(_('Invalid referenced object: %s'), get_class($object)));
        }

    }//end getReferenceSide()


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
        $this->references = $data['references'];
        return true;

    }//end load()


}
