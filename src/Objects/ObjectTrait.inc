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


}
