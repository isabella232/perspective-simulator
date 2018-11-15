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

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;


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


}
