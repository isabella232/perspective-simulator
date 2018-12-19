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
use \PerspectiveSimulator\Libs;

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


    /**
     * Save file for cache.
     *
     * @return boolean
     */
    public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $storeCode  = $this->store->getCode();
        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';

        $classVars = get_object_vars($this);
        $record    = ['type' => get_class($this)];

        unset($classVars['store']);
        foreach ($classVars as $prop => $value) {
            $record[$prop] = $value;
        }

        file_put_contents($filePath, Libs\Util::jsonEncode($record));
        return true;

    }//end save()


    /**
     * Loads from file cache.
     *
     * @return boolean
     */
    public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $storeCode  = $this->store->getCode();
        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$storeCode.'/'.$this->id.'.json';
        if (is_file($filePath) === false) {
            return false;
        }

        $data = Libs\Util::jsonDecode(file_get_contents($filePath));

        foreach ($data as $prop => $value) {
            $this->$prop = $value;
        }

        return true;

    }//end load()


    /**
     * Prepares the value of a file and image property.
     *
     * @param array  $value        The value of the property to validate and prepare.
     * @param string $propertyType The type of the property.
     *
     * @return array
     * @throws \Exception Thrown when the value array is invalid.
     */
    private static function prepareFileImagePropertyValue($value, $propertyType, $propertyCode)
    {
        if (is_array($value) === true) {
            // Expecting the structure of file upload array.
            $requiredFields = [
                'name',
                'type',
                'tmp_name',
                'error',
                'size',
            ];
            foreach ($requiredFields as $field) {
                if (array_key_exists($field, $value) === false) {
                    $errMsg = sprintf(
                        'Expecting \'%s\' field but not found in the value.',
                        $field
                    );
                    throw new \Exception($errMsg);
                }
            }

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

            $uploadedFilepath = Libs\FileSystem::getStorageDir().'/properties/'.$propertyType;
            if (is_dir($uploadedFilepath) === false) {
                Libs\FileSystem::mkdir($uploadedFilepath, true);
            }

            $ext            = Libs\FileSystem::getExtension($value['name']);
            $targetFilepath = $uploadedFilepath.'/'.$propertyCode.'.'.$ext;
            if (move_uploaded_file($value['tmp_name'], $targetFilepath) === false) {
                throw new \Exception('Failed to get the upload file.');
            }

            return '/property/'.str_replace('\\', '/', $GLOBALS['project']).'/'.$propertyType.'/'.$propertyCode.'.'.$ext;
        } else if (is_string($value) === true) {
            // Expecting the base64 string.
            if (preg_match('#^data:[a-z]+/([a-z]+);base64,[\w=+/]++#', $value) !== 1) {
                throw new \Exception('The string value for File/Image property should be a valid base64 string.');
            }

            return $value;
        }//end if

    }//end prepareFileImagePropertyValue()


    /**
     * Get the property type object.
     *
     * @param string $propertyCode The property code.
     *
     * @return object
     */
    final public function property(string $propertyCode)
    {
        $propertyType   = \PerspectiveSimulator\Storage\StorageManager::getStoreType($this);
        $functionName   = 'get'.$propertyType.'Property';
        $unprefixedCode = $propertyCode;
        $propertyCode   = $this->getPrefixedPropertyCode($propertyCode);
        $prop = call_user_func(['\\PerspectiveSimulator\\Storage\\StorageFactory', $functionName], $propertyCode);
        if ($prop === null) {
            throw new \Exception('Property "'.$propertyCode.'" does not exist');
        }

        $className    = 'PerspectiveSimulator\PropertyType\\'.ucfirst($prop['type']);
        if (class_exists($className) === false) {
            // Running an eval instead of creating a file for each property.
            eval('namespace PerspectiveSimulator\PropertyType;
                class '.ucfirst($propertyType).' extends Property {}'
            );
        }

        try {
            $propertyObject = new $className($this, $unprefixedCode, $propertyType);
        } catch (\Throwable $e) {
            // Inside a try catch to ensure we set this flag back to false.
            throw $e;
        }

        return $propertyObject;

    }//end property()


    /**
     * Gets the prefixed property code.
     *
     * @param string $propertyCode The property code we want prefixed.
     *
     * @return string
     */
    final private function getPrefixedPropertyCode(string $propertyCode)
    {
        $bt = debug_backtrace(false);

        // Remove the call to this and the call to the function that needs the property code prefixed.
        array_shift($bt);
        array_shift($bt);

        $key = 0;
        foreach ($bt as $id => $call) {
            if ($call['function'] === 'eval') {
                $key = ($id + 1);
                break;
            }
        }

        $called = $bt[$key];
        if (strpos(strtolower($called['class']), strtolower($GLOBALS['project'])) === false) {
            $classParts   = explode('\\', $called['class']);
            $propertyCode = Bootstrap::generatePrefix($classParts[0].'\\'.$classParts[1]).'-'.$propertyCode;
        } else {
            $propertyCode = Bootstrap::generatePrefix($GLOBALS['project']).'-'.$propertyCode;
        }

        return $propertyCode;

    }//end getPrefixedPropertyCode()


}
