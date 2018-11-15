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
     * The record id.
     *
     * @var string
     */
    private $id = '';

    /**
     * The store the record belongs to.
     *
     * @var object
     */
    private $store = '';

    /**
     * Array of record properties attached to the record.
     *
     * @var array
     */
    private $properties = [];


    /**
     * Gets the internal ID of the record.
     *
     * @return string
     */
    final public function getId()
    {
        return $this->id;

    }//end getId()


    /**
     * Gets the store that the record is contained within.
     *
     * @return object
     */
    final public function getStorage()
    {
        return $this->store;

    }//end getStorage()


}
