<?php
/**
 * StorageManager class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Storage;

/**
 * StorageManager Class.
 */
class StorageManager
{


    /**
     * Gets the object type.
     *
     * @param object $object The object we are calling from.
     *
     * @return mixed
     */
    public static function getStoreType($object)
    {
        $class = get_class($object);
        if (is_subclass_of($object, 'PerspectiveSimulator\\ObjectType\\DataRecord') === true
            || $class === 'PerspectiveSimulator\\ObjectType\\DataRecord'
        ) {
            return 'DataRecord';
        } else if (is_subclass_of($object, 'PerspectiveSimulator\\ObjectType\\User') === true
            || $class === 'PerspectiveSimulator\\ObjectType\\User'
        ) {
            return 'User';
        } else if (is_subclass_of($object, 'PerspectiveSimulator\\ObjectType\\Deployment') === true
            || $class === 'PerspectiveSimulator\\ObjectType\\Deployment'
        ) {
            return 'Deployment';
        }

        return null;

    }//end getStoreType()

}//end class
