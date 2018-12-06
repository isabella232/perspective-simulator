<?php
/**
 * FileTrait for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Property;

require_once dirname(__FILE__).'/AspectTrait.inc';

use \PerspectiveSimulator\Property\AspectTrait as AspectTrait;

/**
 * FileTrait Trait.
 */
trait FileTrait
{

    use AspectTrait;

    /**
     * Get aspect to query properties with.
     *
     * This is called inside the AspectedTrait to always retrive the latest aspect set on the owner object.
     *
     * @return array
     */
    private function getAspect()
    {
        return $this->getObject()->getAspect();

    }//end getAspect()


    /**
     * Serve file type property content.
     *
     * When sendFileHeader() function is used in inline PHP code, it uses this action to send X-Sendfile
     * header for file type property. For any reason if it can't send header, then it returns false.
     *
     * @param string $shadowid The optional shadowid.
     *
     * @return void|boolean
     */
    final public function sendFileHeader(string $shadowid=null)
    {
        $this->validateObjectid();
        $objectid = $this->getObjectid();

        $shadowids = [];
        if ($shadowid !== null) {
            $shadowids[] = $shadowid;
        }

        $value = $this->getValue();
        \PerspectiveSimulator\Libs\FileSystem::serveFile($value);

    }//end sendFileHeader()

}//end trait