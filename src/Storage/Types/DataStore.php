<?php
/**
 * DataStore class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\StorageType;

require_once dirname(__FILE__, 2).'/StoreTrait.inc';

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\ObjectType\DataRecord;
use \PerspectiveSimulator\Storage\StoreTrait as StoreTrait;

/**
 * DataStore class
 */
class DataStore
{

    use StoreTrait;


    /**
     * Constructor for DataStore Class.
     *
     * @param string $code The name of the user store.
     *
     * @return void
     */
    public function __construct(string $code)
    {
        $this->code = $code;
        $this->type = 'Data';

        if (Bootstrap::isWriteEnabled() === true) {
            $storageDir = \PerspectiveSimulator\Libs\FileSystem::getStorageDir();
            $storeDir   = $storageDir.'/'.$code;
            if (is_dir($storeDir) === false) {
                \PerspectiveSimulator\Libs\FileSystem::mkdir($storeDir);
            }
        }

        $this->load();

    }//end __construct()


    /**
     * Creates a new data record inside the store.
     *
     * @param string $type   The custom data type to apply to the new record.
     *                       If NULL, no custom data type will be applied.
     * @param string $parent The ID of the parent data record under which the new data record will be created
     *                       If NULL, the data record will be created at the top level of the tree.
     *
     * @return object
     * @throws \Exception When parent doesn't exist.
     */
    final public function createDataRecord(string $type=null, string $parent=null)
    {
        if ($type === null) {
            $type = 'PerspectiveSimulator\ObjectType\DataRecord';
        } else {
            $type = $GLOBALS['project'].'\CustomTypes\Data\\'.$type;
        }

        if ($parent !== null && isset($this->records[$parent]) === false) {
            throw new \Exception('Parent "'.$parent.'" does not exist');
        }

        $recordid = ($this->numRecords++).'.1';
        $record   = new $type($this, $recordid);

        $this->records[$recordid] = [
            'object'   => $record,
            'depth'    => 1,
            'children' => [],
            'parent'   => $parent,
        ];

        if ($parent !== null) {
            $this->records[$parent]['children'][$recordid] = $record;
            $this->records[$recordid]['depth']            += $this->records[$parent]['depth'];
        }

        $this->save();

        return $record;

    }//end createDataRecord()


    /**
     * Retrieves a data record from the data store
     *
     * @param string $recordid The ID of a data record.
     *
     * @return object
     */
    final public function getDataRecord(string $recordid)
    {
        return ($this->records[$recordid]['object'] ?? null);

    }//end getDataRecord()


    /**
     * Retrieves a data record from the data store based on the value of a unique property
     *
     * @param string $propertyCode The ID of the unique property.
     * @param string $value        The value of the unique property.
     *
     * @return object
     */
    final public function getUniqueDataRecord(string $propertyCode, string $value)
    {
        return ($this->uniqueMap[$propertyCode][$value] ?? null);

    }//end getUniqueDataRecord()


    /**
     * Sets a data record from the data store based on the value of a unique property
     *
     * @param string $propertyCode The ID of the unique property.
     * @param string $value        The value of the unique property.
     * @param mixed  $record       The record to store.
     *
     * @return void
     */
    final public function setUniqueDataRecord(string $propertyCode, string $value, $record)
    {
        $this->uniqueMap[$propertyCode][$value] = $record;
        $this->save();

    }//end setUniqueDataRecord()


    /**
     * Gets the children of a data record.
     *
     * @param string  $recordid The ID of the data record.
     * @param integer $depth    The number of levels to retrive.
     *
     * @return array
     */
    final public function getChildren(string $recordid, int $depth=null)
    {
        if (isset($this->records[$recordid]) === false) {
            return [];
        }

        if ($depth !== null) {
            if ($depth === 0) {
                return [];
            }

            $depth--;
        }

        $children = [];
        foreach ($this->records[$recordid]['children'] as $childid => $child) {
            $children[$childid] = [
                'depth'    => $this->records[$childid]['depth'],
                'children' => [],
            ];

            if ($depth !== 0) {
                $children[$childid]['children'] = $this->getChildren($childid, $depth);
            }
        }

        return $children;

    }//end getChildren()


    /**
     * Returns a flat list of data record's parents.
     *
     * @param string  $recordid The ID of the data record.
     * @param integer $depth    The max depth.
     *
     * @return array
     */
    final public function getParents(string $recordid, int $depth=null)
    {
        if (isset($this->records[$recordid]) === false) {
            return [];
        }

        if ($depth !== null) {
            if ($depth === 0) {
                return [];
            }

            $depth--;
        }

        $parents = [];
        if ($this->records[$recordid]['parent'] !== null) {
            $parentid           = $this->records[$recordid]['parent'];
            $parents[$parentid] = [
                'depth'   => $this->records[$parentid]['depth'],
                'parents' => [],
            ];

            if ($depth !== 0) {
                $parents[$parentid]['parents'] = $this->getParents($parentid, $depth);
            }
        }

        return $parents;

    }//end getParents()


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
            $children = [];
            if (isset($data['children']) === true) {
                $children = (array_keys($data['children']) ?? []);
            }

            $store['records'][$recordid] = [
                'depth'    => ($data['depth'] ?? null),
                'children' => $children,
                'parent'   => ($data['parent'] ?? null),
            ];
        }

        foreach ($this->uniqueMap as $propid => $values) {
            $store['uniqueMap'][$propid] = [];
            foreach ($values as $value => $record) {
                $store['uniqueMap'][$propid][$value] = $record->getId();
            }
        }

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';
        file_put_contents($filePath, Libs\Util::jsonEncode($store));
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

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';

        if (is_file($filePath) === false) {
            return false;
        }

        $store = Libs\Util::jsonDecode(file_get_contents($filePath));

        foreach ($store['records'] as $recordid => $data) {
            $recordPath = dirname($filePath).'/'.$recordid.'.json';
            $recordData = Libs\Util::jsonDecode(file_get_contents($recordPath));
            $type       = $recordData['type'];
            $data['object'] = new $type($this, $recordid);

            $this->records[$recordid] = $data;

            $baseRecordid     = (int) substr($recordid, 0, -2);
            $this->numRecords = max($this->numRecords, $baseRecordid);
        }//end foreach

        foreach ($store['records'] as $recordid => $data) {
            $children = [];
            foreach ($data['children'] as $childid) {
                $children[$childid] = $this->records[$childid]['object'];
            }

            $this->records[$recordid]['children'] = $children;
            $this->records[$recordid]['parent']   = $data['parent'];
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


}//end class
