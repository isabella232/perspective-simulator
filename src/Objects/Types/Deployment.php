<?php
/**
 * Deployment class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\ObjectType;

require_once dirname(__FILE__, 2).'/AspectedObjectTrait.inc';
require_once dirname(__FILE__, 2).'/AspectedObjectWriteTrait.inc';
require_once dirname(__FILE__, 2).'/ObjectReadInterface.inc';
require_once dirname(__FILE__, 2).'/ObjectWriteInterface.inc';

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\Objects\AspectedObjectTrait as AspectedObjectTrait;
use \PerspectiveSimulator\Objects\AspectedObjectWriteTrait as AspectedObjectWriteTrait;
use \PerspectiveSimulator\Objects\ObjectReadInterface as ObjectReadInterface;
use \PerspectiveSimulator\Objects\ObjectWriteInterface as ObjectWriteInterface;

/**
 * Deployment Class
 */
class Deployment implements ObjectReadInterface, ObjectWriteInterface
{

    use AspectedObjectTrait;

    use AspectedObjectWriteTrait;



    /**
     * Class Constructor.
     *
     * @param string $projectid ID of the project or sub project (deployment)
     *
     * @return void
     */
    final public function __construct(string $projectid)
    {
        $this->id = $projectid;

        if ($this->load() === false) {
            $this->save();
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

        $storageDir = Libs\FileSystem::getSimulatorDir();
        $filePath   = $storageDir.'/'.$GLOBALS['project'].'/'.$this->id.'.json';

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

        $storageDir = Libs\FileSystem::getSimulatorDir();
        $filePath   = $storageDir.'/'.$GLOBALS['project'].'/'.$this->id.'.json';
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
