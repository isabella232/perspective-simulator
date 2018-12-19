<?php
/**
 * ObjectReadInterface
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

/**
 * ObjectReadInterface
 */
interface ObjectInterface
{


    /**
     * Gets the value of a given property.
     *
     * @param string $propertyCode The code of the property that is being retrieved.
     *
     * @return mixed
     */
    public function getValue(string $propertyCode);


    /**
     * Load from file cache.
     *
     * @return boolean
     */
    public function load();


    /*
     * Sets the value of a given property.
     *
     * @param string $propertyCode The code of the property that the value is being set into.
     * @param mixed  $value        The value to set into the property.
     *
     * @return void
     */
    public function setValue(string $propertyCode, $value);


    /**
     * Deletes the value of a given property.
     *
     * @param string $propertyCode The code of the property that the value is being deleted from.
     *
     * @return void
     */
    public function deleteValue(string $propertyCode);


    /**
     * Save to file for cache.
     *
     * @return boolean
     */
    public function save();


}//end interface