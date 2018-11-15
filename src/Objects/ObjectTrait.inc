<?php
/**
 * StoreTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

use \PerspectiveSimulator\Bootstrap;

/**
 * StoreTrait Trait.
 */
trait ObjectTrait
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


}
