<?php
/**
 * AspectedObjectTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Objects;

require_once dirname(__FILE__).'/ObjectTrait.inc';

use \PerspectiveSimulator\Storage;


/**
 * AspectedObjectTrait Trait.
 */
trait AspectedObjectTrait
{

    use ObjectTrait;

    /**
     * The aspect to query data record properties.
     *
     * @var array
     */
    private $aspect = null;


    /**
     * Set aspect to query properties with.
     *
     * @param array $aspect The property aspect.
     *
     * @return void
     */
    final public function setAspect(array $aspect=null)
    {
        $this->aspect = $aspect;

    }//end setAspect()


    /**
     * Get aspect to query properties with.
     *
     * @return array
     */
    final public function getAspect()
    {
        return $this->aspect;

    }//end getAspect()


    /**
     * Gets the value of a given property for a given page in a given aspect.
     *
     * Returns the value of the specified property. If the page does not have a specific value set for the given
     * property, an inherited value will be returned if available. If no inherited value is available, the default value
     * of the property will be returned.
     *
     * @param string $propertyCode The property code that is being retrieved.
     *
     * @return mixed
     * @throws InvalidDataException When propertyid is not a valid propertyid.
     */
    final public function getValue(string $propertyCode)
    {
        $typeName = Storage\StorageManager::getStoreType($this);
        if ($typeName === null) {
            throw new \Exception('Invalid property type');
        }

        $functionName = 'get'.$typeName.'Property';
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if (isset($this->properties[$propertyCode]) === true) {
            return $this->properties[$propertyCode];
        }

        return $prop['default'];

    }//end getValue()


    /**
     * Gets the shadow value of a given property for a given page in a given aspect.
     *
     * This method can also be used to retrieve multiple shadow values for multiple pages and so has a number of return
     * value formats.
     *
     * @param string $propertyCode The property code that is being retrieved.
     * @param string $shadowid     The shadow ID that the value is being retrieved from. If passing an array, the array
     *                             must be a single-dimensional list of shadow IDs. If NULL, all values with a shadow ID
     *                             will be returned for all passed page IDs. i.e., no shadow ID filtering will take place.
     *
     * @return mixed
     * @throws InvalidDataException When propertyid is not known.
     */
    final public function getShadowValue(string $propertyCode, string $shadowid=null)
    {
        // TODO: Implement this.

    }//end getShadowValue()


}
