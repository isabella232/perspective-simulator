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
     * Load from file cache.
     *
     * @return boolean
     */
    public function load();


}//end interface