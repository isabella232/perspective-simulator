<?php
/**
 * Data Record class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace Perspective\PHPClass\ObjectType;

require_once dirname(__FILE__, 2).'/AspectedObjectTrait.inc';
require_once dirname(__FILE__, 2).'/ReferenceObjectTrait.inc';

use \PerspectiveAPI\Object\Types\DataRecord as PerspectiveAPIDataRecord;

use \PerspectiveSimulator\Objects\AspectedObjectTrait as AspectedObjectTrait;
use \PerspectiveSimulator\Objects\ReferenceObjectTrait as ReferenceObjectTrait;

/**
 * DataRecord Class
 */
class DataRecord extends PerspectiveAPIDataRecord
{

    use AspectedObjectTrait;

    use ReferenceObjectTrait;


    /**
     * Construct function for Data Record.
     *
     * @param object $store The store the data record belongs to.
     * @param string $id    The id of the data record.
     *
     * @return void
     */
    final public function __construct(\PerspectiveSimulator\StorageType\DataStore $store, string $id)
    {
        parent::__construct($store, $id);

        if ($this->load() === false) {
            \PerspectiveSimulator\Bootstrap::queueSave($this);
        }

    }//end __construct()


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

    }//end getParents()


}//end class
