<?php
namespace PerspectiveSimulator;

class DataStore
{
    private $code = '';
    private $records = [];
    private $numRecords = 1;
    private $uniqueMap = [];


    function __construct($code)
    {
        $this->code = $code;
    }


    final public function createDataRecord(string $type=null, string $parent=null)
    {
        if ($type === null) {
            $type = 'PerspectiveSimulator\DataRecord';
        } else {
            $trace   = debug_backtrace();
            $project = substr($trace[1]['class'], 0, strpos($trace[1]['class'], '\\'));
            $type = $project.'\CustomTypes\DataRecord\\'.$type;
        }

        if ($parent !== null && isset($this->records[$parent]) === false) {
            throw new \Exception("Parent \"$parent\" does not exist");
        }

        $recordid = $this->numRecords++.'.1';
        $record   = new $type($this, $recordid);

        $this->records[$recordid] = [
            'object'   => $record,
            'depth'    => 1,
            'children' => [],
        ];

        if ($parent !== null) {
            $this->records[$parent]['children'][$recordid] = $record;
            $this->records[$recordid]['depth'] += $this->records[$parent]['depth'];
        }

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
}
