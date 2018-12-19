<?php
/**
 * ProjectInstance class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\ObjectType;

require_once dirname(__FILE__, 2).'/AspectedObjectTrait.inc';
require_once dirname(__FILE__, 2).'/ObjectInterface.inc';

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\Objects\AspectedObjectTrait as AspectedObjectTrait;
use \PerspectiveSimulator\Objects\ObjectInterface as ObjectInterface;

/**
 * ProjectInstance Class
 */
class ProjectInstance implements ObjectInterface
{

    use AspectedObjectTrait;


    /**
     * Class Constructor.
     *
     * @param string $projectid ID of the project or sub project (deployment)
     *
     * @return void
     */
    final public function __construct(string $projectid)
    {
        $projectid = str_replace('\\', '-', $projectid);
        $projectid = str_replace('/', '-', $projectid);
        $this->id  = $projectid;

        if ($this->load() === false) {
            Bootstrap::queueSave($this);
        }

    }//end __construct()


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

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->id.'.json';

        $classVars = get_object_vars($this);
        $record    = [];

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

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->id.'.json';
        if (is_file($filePath) === false) {
            return false;
        }

        $data = Libs\Util::jsonDecode(file_get_contents($filePath));

        foreach ($data as $prop => $value) {
            $this->$prop = $value;
        }

        return true;

    }//end load()


}//end class
