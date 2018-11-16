<?php
/**
 * ObjectWriteInterface
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

/**
 * ObjectWriteInterface
 */
interface ObjectWriteInterface
{


    /**
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
     * Sets the shadow value of a given property.
     *
     * @param string $propertyCode The code of the property that the value is being set into.
     * @param string $shadowid     The shadow ID to associate with the value.
     * @param mixed  $value        The value to set into the property.
     *
     * @return void
     */
    public function setShadowValue(string $propertyCode, string $shadowid, $value);


    /**
     * Deletes the shadow value of a given property.
     *
     * @param string $propertyCode The code of the property that the value is being deleted from.
     * @param string $shadowid     The shadow ID to associate with the value.
     *
     * @return void
     */
    public function deleteShadowValue(string $propertyCode, string $shadowid);


    /**
     * Save to file for cache.
     *
     * @return boolean
     */
    public function save();


}//end interface
