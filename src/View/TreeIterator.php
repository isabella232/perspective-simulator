<?php
/**
 * Iterator class for Twig tree structure.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\View;

/**
 * TreeIterator Class.
 */
class TreeIterator implements \RecursiveIterator
{

    /**
     * The tree array to iterate.
     *
     * @var array
     */
    private $array = null;

    /**
     * The current iteration position.
     *
     * @var integer
     */
    private $position = null;

    /**
     * The total number of items in the current level.
     *
     * @var integer
     */
    private $total = 0;


    /**
     * Constructor
     *
     * @param array $array Tree array.
     *
     * @return void
     */
    public function __construct(array $array)
    {
        $this->array    = $array;
        $this->position = 0;
        $this->total    = count($array);

    }//end __construct()


    /**
     * Returns TreeIterator object for children.
     *
     * @return TreeIterator
     */
    public function getChildren()
    {
        return new TreeIterator($this->array[$this->position]['children']);

    }//end getChildren()


    /**
     * Returns true if the current item has children.
     *
     * @return boolean
     */
    public function hasChildren()
    {
        if (empty($this->array[$this->position]) === true) {
            return false;
        }

        return true;

    }//end hasChildren()


    /**
     * Returns the current item.
     *
     * @return array
     */
    public function current()
    {
        $current = $this->array[$this->position];
        unset($current['children']);
        return $current;

    }//end current()


    /**
     * Returns the key of the current item.
     *
     * @return integer
     */
    public function key()
    {
        return $this->position;

    }//end key()


    /**
     * Moves the position forward by 1.
     *
     * @return void
     */
    public function next()
    {
        $this->position++;

    }//end next()


    /**
     * Rewinds the position back to the beginning.
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;

    }//end rewind()


    /**
     * Returns true if the current position is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        if ($this->total > $this->position) {
            return true;
        }

        return false;

    }//end valid()


}//end class
