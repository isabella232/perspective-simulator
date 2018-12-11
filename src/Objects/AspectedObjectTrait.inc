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
     * @throws \Exception When propertyid is not a valid propertyid.
     */
    final public function getValue(string $propertyCode)
    {
        $typeName = Storage\StorageManager::getStoreType($this);
        if ($typeName === null) {
            throw new \Exception('Invalid property type');
        }

        $functionName = 'get'.$typeName.'Property';
        $prop         = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if (isset($this->properties[$propertyCode]) === true) {
            return $this->properties[$propertyCode];
        }

        if ($prop['type'] === 'image' || $prop['type'] === 'file') {
            if ($typeName === 'Deployment') {
                $typeName = 'Project';
            }

            $propFiles = \PerspectiveSimulator\Libs\FileSystem::listDirectory(
                \PerspectiveSimulator\Libs\FileSystem::getProjectDir().'/Properties/'.$typeName,
                [],
                false,
                false
            );

            foreach ($propFiles as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) === '.json'
                    || strpos($file, $propertyCode) !== 0
                ) {
                    continue;
                }

                $prop['default'] = $file;
            }

            return '/property/'.$GLOBALS['project'].'/'.$typeName.'/'.$prop['default'];
        }

        return $prop['default'];

    }//end getValue()


}
