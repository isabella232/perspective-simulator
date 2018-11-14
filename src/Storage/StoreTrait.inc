<?php
/**
 * StoreTrait.
 *
 * @package    Perspective
 * @subpackage Template
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2010 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Storage;

use \PerspectiveSimulator\Bootstrap;

/**
 * StoreTrait Trait.
 */
trait StoreTrait
{

    /**
     * The type of the record store.
     *
     * @var string
     */
    private $type = '';

    /**
     * The code of the record store.
     *
     * @var string
     */
    private $code = '';

    /**
     * Array of all the records.
     *
     * @var array
     */
    private $records = [];

    /**
     * Counter of records.
     *
     * @var integer
     */
    private $numRecords = 1;

    /**
     * Map of unique records.
     *
     * @var array
     */
    private $uniqueMap = [];



    /**
     * Gets the Stores type.
     *
     * @return string
     */
    final public function getType()
    {
        return $this->type;

    }//end getCode()


    /**
     * Gets the Stores code.
     *
     * @return string
     */
    final public function getCode()
    {
        return $this->code;

    }//end getCode()


    /**
     * Save the store as a json object.
     *
     * @return boolean
     */
    final public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $store = [
            'records'   => [],
            'uniqueMap' => [],
        ];

        foreach ($this->records as $recordid => $data) {
            $store['records'][$recordid] = [
                'depth'    => ($data['depth'] ?? null),
                'children' => (array_keys($data['children']) ?? []),
            ];
        }

        foreach ($this->uniqueMap as $propid => $values) {
            $store['uniqueMap'][$propid] = [];
            foreach ($values as $value => $record) {
                $store['uniqueMap'][$propid][$value] = $record->getId();
            }
        }

        $storageDir = Bootstrap::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';
        file_put_contents($filePath, json_encode($store, JSON_PRETTY_PRINT));
        return true;

    }//end save()


    /**
     * Loads the Stores cache.
     *
     * @return boolean
     */
    public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $storageDir = Bootstrap::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';

        if (is_file($filePath) === false) {
            return false;
        }

        $store = json_decode(file_get_contents($filePath), true);

        foreach ($store['records'] as $recordid => $data) {
            $recordPath     = dirname($filePath).'/'.$recordid.'.json';
            $recordData     = json_decode(file_get_contents($recordPath), true);
            $type           = $recordData['type'];
            $data['object'] = new $type($this, $recordid);
            $this->records[$recordid] = $data;

            $baseRecordid     = (int) substr($recordid, 0, -2);
            $this->numRecords = max($this->numRecords, $baseRecordid);
        }

        foreach ($store['records'] as $recordid => $data) {
            $children = [];
            foreach ($data['children'] as $childid) {
                $children[$childid] = $this->records[$childid]['object'];
            }

            $this->records[$recordid]['children'] = $children;
        }

        foreach ($store['uniqueMap'] as $propid => $values) {
            $this->uniqueMap[$propid] = [];
            foreach ($values as $value => $recordid) {
                $this->uniqueMap[$propid][$value] = $this->records[$recordid]['object'];
            }
        }

        $this->numRecords++;

        return true;

    }//end load()


}