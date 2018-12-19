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
use \PerspectiveSimulator\Bootstrap;

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

        $unprefixedCode = $propertyCode;
        $propertyCode   = $this->getPrefixedPropertyCode($propertyCode);

        $functionName = 'get'.$typeName.'Property';
        $prop         = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $unprefixedCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if (isset($this->properties[$propertyCode]) === true) {
            return $this->properties[$propertyCode];
        }

        if ($prop['type'] === 'image' || $prop['type'] === 'file') {
            $propDir        = \PerspectiveSimulator\Libs\FileSystem::getProjectDir().'/Properties/'.$typeName;
            $unprefixedCode = explode('-', $propertyCode);
            $prefix         = array_shift($unprefixedCode);
            $prefix        .= '/'.array_shift($unprefixedCode);
            if ($prefix === strtolower($GLOBALS['project'])) {
                $unprefixedCode = implode('-', $unprefixedCode);
            } else {
                $prefix         = str_replace('\\', '/', $prefix);
                $unprefixedCode = implode('-', $unprefixedCode);
                $propDir        = substr(\PerspectiveSimulator\Libs\FileSystem::getProjectDir(), 0, -4);
                $propDir       .= '/vendor/'.$prefix.'/src/Properties/'.$typeName;
            }

            $propFiles = \PerspectiveSimulator\Libs\FileSystem::listDirectory(
                $propDir,
                [],
                false,
                false
            );

            foreach ($propFiles as $file) {
                if ($file[0] === '.'
                    || substr($file, -5) === '.json'
                    || strpos($file, $unprefixedCode) !== 0
                ) {
                    continue;
                }

                $prop['default'] = $file;
            }

            $prefix = str_replace('/', '-', str_replace('\\', '-', $prefix));
            return '/property/'.str_replace('\\', '/', $GLOBALS['project']).'/'.$typeName.'/'.strtolower($prefix).'-'.$prop['default'];
        }

        return $prop['default'];

    }//end getValue()


    /**
     * Sets the value of a given property for a given record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being set on.
     * @param mixed  $value        The value to set into the property.
     *
     * @return void
     * @throws \Exception When the propertyCode doesn't exist or the value isn't unique.
     */
    final public function setValue(string $propertyCode, $value)
    {
        $typeName = Storage\StorageManager::getStoreType($this);
        if ($typeName === null) {
            throw new \Exception('Invalid property type');
        }

        $unprefixedCode = $propertyCode;
        $propertyCode   = $this->getPrefixedPropertyCode($propertyCode);

        $functionName = 'get'.$typeName.'Property';
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $unprefixedCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        if ($prop['type'] === 'unique') {
            $current = $this->store->getUniqueDataRecord($unprefixedCode, $value);
            if ($current !== null) {
                throw new \Exception('Unique value "'.$value.'" is already in use');
            }

            $this->store->setUniqueDataRecord($unprefixedCode, $value, $this);
        } else if ($prop['type'] === 'image' || $prop['type'] === 'file') {
            $value = self::prepareFileImagePropertyValue($value, $typeName, $propertyCode);
        }

        $this->properties[$propertyCode] = $value;

        \PerspectiveSimulator\Bootstrap::queueSave($this);

    }//end setValue()


    /**
     * Deletes the set value of a given property for a given data record in a given aspect.
     *
     * @param string $propertyCode The property code that the value is being deleted from.
     *
     * @return void
     * @throws \Exception When the propertyCode doesn't exist.
     */
    final public function deleteValue(string $propertyCode)
    {
        if (isset($this->properties[$propertyCode]) === true) {
            unset($this->properties[$propertyCode]);
            \PerspectiveSimulator\Bootstrap::queueSave($this);
        }

    }//end deleteValue()


}
