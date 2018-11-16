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
interface ObjectReadInterface
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
     * Gets the shadow value of a given property.
     *
     * @param string $propertyCode The code of the property that is being retrieved.
     * @param string $shadowid     The shadow ID that the value is being retrieved from.
     *                             If passing an array, the array must be a single-dimensional list of shadow IDs.
     *                             If NULL, all values with a shadow ID will be returned for all passed propertyid.
     *
     * @return mixed
     */
    public function getShadowValue(string $propertyCode, string $shadowid=null);


    /**
     * Load from file cache.
     *
     * @return boolean
     */
    public function load();


}//end interface