<?php
/**
 * StoreTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Storage;

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;

/**
 * StoreTrait Trait.
 */
trait StoreTrait
{

    /**
     * The type of the record store.
     *
     * @var string
     */
    private $type = '';

    /**
     * The code of the record store.
     *
     * @var string
     */
    private $code = '';

    /**
     * Array of all the records.
     *
     * @var array
     */
    private $records = [];

    /**
     * Counter of records.
     *
     * @var integer
     */
    private $numRecords = 1;

    /**
     * Map of unique records.
     *
     * @var array
     */
    private $uniqueMap = [];


    /**
     * Gets the Stores type.
     *
     * @return string
     */
    final public function getType()
    {
        return $this->type;

    }//end getType()


    /**
     * Gets the Stores code.
     *
     * @return string
     */
    final public function getCode()
    {
        return $this->code;

    }//end getCode()


}
