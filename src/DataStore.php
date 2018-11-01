<?php
namespace PerspectiveSimulator;

class DataStore
{
    private $code = '';
    private $project = '';
    private $records = [];
    private $numRecords = 1;
    private $uniqueMap = [];


    function __construct($code, $project)
    {
        $this->code = $code;
        $this->project = $project;

        if (Bootstrap::isWriteEnabled() === true) {
            $storageDir = dirname(__DIR__, 4).'/simulator/'.$this->project.'/storage';
            if (is_dir($storageDir) === false) {
                mkdir($storageDir);
            }

            $storeDir = $storageDir.'/'.$code;
            if (is_dir($storeDir) === false) {
                mkdir($storeDir);
            }
        }

        $this->load();

    }//end __construct()


    final public function createDataRecord(string $type=null, string $parent=null)
    {
        if ($type === null) {
            $type = 'PerspectiveSimulator\DataRecord';
        } else {
            $trace   = debug_backtrace();
            foreach ($trace as $id => $data) {
                if ($id === 0 || isset($data['class']) === false) {
                    continue;
                }

                $project = substr($data['class'], 0, strpos($data['class'], '\\'));
                $type = $project.'\CustomTypes\Data\\'.$type;
                break;
            }
        }

        if ($parent !== null && isset($this->records[$parent]) === false) {
            throw new \Exception("Parent \"$parent\" does not exist");
        }

        $recordid = $this->numRecords++.'.1';
        $record   = new $type($this, $recordid, $this->project);

        $this->records[$recordid] = [
            'object'   => $record,
            'depth'    => 1,
            'children' => [],
        ];

        if ($parent !== null) {
            $this->records[$parent]['children'][$recordid] = $record;
            $this->records[$recordid]['depth'] += $this->records[$parent]['depth'];
        }

        $this->save();

        return $record;
    }


    final public function getDataRecord(string $recordid)
    {
        return $this->records[$recordid]['object'] ?? null;
    }


    final public function getUniqueDataRecord(string $propertyCode, string $value)
    {
        return $this->uniqueMap[$propertyCode][$value] ?? null;
    }

    final public function setUniqueDataRecord(string $propertyCode, string $value, $record)
    {
        $this->uniqueMap[$propertyCode][$value] = $record;
        $this->save();
    }



    final public function getChildren($recordid, $depth=null)
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
                'depth' => $this->records[$childid]['depth'],
                'children' => [],
            ];

            if ($depth !== 0) {
                $children[$childid]['children'] = $this->getChildren($childid, $depth);
            }
        }

        return $children;

    }//end getChildren()

    final public function getCode()
    {
        return $this->code;

    }//end getCode()


    final public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $store = [
            'records' => [],
            'uniqueMap' => [],
        ];

        foreach ($this->records as $recordid => $data) {
            $store['records'][$recordid] = [
                'depth' => $data['depth'],
                'children' => array_keys($data['children']),
            ];
        }

        foreach ($this->uniqueMap as $propid => $values) {
            $store['uniqueMap'][$propid] = [];
            foreach ($values as $value => $record) {
                $store['uniqueMap'][$propid][$value] = $record->getId();
            }
        }

        $filePath = dirname(__DIR__, 4).'/simulator/'.$this->project.'/storage/'.$this->code.'/store.json';
        file_put_contents($filePath, json_encode($store));
        return true;

    }//end save()


    final public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $filePath = dirname(__DIR__, 4).'/simulator/'.$this->project.'/storage/'.$this->code.'/store.json';

        if (is_file($filePath) === false) {
            return false;
        }

        $store = json_decode(file_get_contents($filePath), true);

        foreach ($store['records'] as $recordid => $data) {
            $recordPath = dirname($filePath).'/'.$recordid.'.json';
            $recordData = json_decode(file_get_contents($recordPath), true);
            $type = $recordData['type'];
            $data['object'] = new $type($this, $recordid, $this->project);
            $this->records[$recordid] = $data;

            $baseRecordid = (int) substr($recordid, 0, -2);
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


}//end class
