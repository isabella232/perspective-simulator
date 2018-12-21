<?php
/**
 * ReferenceObjectTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

use \PerspectiveSimulator\Bootstrap;

/**
 * ReferenceObjectTrait Trait.
 */
trait ReferenceObjectTrait
{

    /**
     * Array of references attached to the record.
     *
     * @var array
     */
    private $references = [];


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
        if (isset($this->references[$referenceid]) === false || empty($this->references[$referenceid]) === true) {
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
        }

        $code       = $this->store->getCode();
        $storeType  = $this->store->getType();
        $storageDir = \PerspectiveSimulator\Libs\FileSystem::getProjectDir();
        $filePath   = $storageDir.'/Stores/'.$storeType.'/'.$code.'/'.$referenceid.'.json';
        if (file_exists($filePath) === true) {
            $reference     = \PerspectiveSimulator\Libs\Util::jsonDecode(file_get_contents($filePath));
            $sourceValue   = [];
            $targetValue   = [];
            $referenceSide = $this->getReferenceSide($reference, $this);
            if ($referenceSide === 'source') {
                $sourceValue[] = $this->getId();
            } else if ($referenceSide === 'target') {
                $targetValue[] = $this->getId();
            }

            if ($reference['cardinality'] === '1:1') {
                if (count($sourceValue) === 1 || count($targetValue) === 1) {
                    unset($this->references[$referenceid]);
                }
            } else if ($reference['cardinality'] === '1:M') {
                if (count($sourceValue) !== 1) {
                    unset($this->references[$referenceid]);
                }
            }
        }//end if

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

        \PerspectiveSimulator\Bootstrap::queueSave($this);

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

        \PerspectiveSimulator\Bootstrap::queueSave($this);

    }//end deleteReference()


    /**
     * Validates if the reference can be set.
     *
     * @param string  $referenceid The id of the reference we are trying to set.
     * @param array   $objects     The objects we are setting the reference against, used if we are setting the reference
     *                             for the other side.
     * @param boolean $reverse     Flag if we are checking the reverse side of a reference.
     *
     * @return boolean.
     * @throws \Exception When reference is invalid.
     */
    final public function validateReference(string $referenceid, array $objects=[], bool $reverse=false)
    {
        $valid     = false;
        $code      = $this->store->getCode();
        $storeType = $this->store->getType();

        $storageDir = \PerspectiveSimulator\Libs\FileSystem::getProjectDir();
        $filePath   = $storageDir.'/Stores/'.$storeType.'/'.$code.'/'.$referenceid.'.json';
        if (file_exists($filePath) === true) {
            $reference   = \PerspectiveSimulator\Libs\Util::jsonDecode(file_get_contents($filePath));
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

            $errorMsg = 'Expecting single %s value in %s cardinality, but %s given';
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
                    throw new \Exception('The target must be the object itself');
                }

                $valid = true;
            } else {
                if ($targetValue[0] !== $this->getId()) {
                    throw new \Exception('The source must be the object itself');
                }

                if ($reference['cardinality'] === 'M:M' && count($sourceValue) > 1) {
                    $valid = true;
                } else {
                    $valid = true;
                }
            }//end if
        }//end if

        if ($valid === false && empty($objects) === false && $reverse === false) {
            // Must be validating the reverse reference so check the objects to see if its valid.
            // Don't run this again on the reverse check as it will end up looping.
            foreach ($objects as $object) {
                $valid = $object->validateReference($referenceid, [$this], true);
                if ($valid === true) {
                    break;
                }
            }
        } else {
            // If we've got this far on the reverse lookup then it must have been vaild from the initial check, so lets
            // make it valid.
            $valid = true;
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
            throw new \Exception('Invalid referenced object: is not an object');
        }

        if ($object instanceof \Perspective\PHPClass\ObjectType\User) {
            $storageClass = 'UserStore';
        } else if ($object instanceof \Perspective\PHPClass\ObjectType\DataRecord) {
            $storageClass = 'DataStore';
        } else {
            throw new \Exception(
                sprintf('Invalid referenced object: invalid object type: %s'),
                get_class($object)
            );
        }

        $storageCode = $object->getStorage()->getCode();
        if ($reference['sourceType'] === $storageClass && $reference['sourceCode'] === $storageCode) {
            return 'source';
        } else if ($reference['targetType'] === $storageClass && $reference['targetCode'] === $storageCode) {
            return 'target';
        } else {
            throw new \Exception(sprintf('Invalid referenced object: %s'), get_class($object));
        }

    }//end getReferenceSide()


}
