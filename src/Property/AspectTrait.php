<?php
/**
 * AspectTrait.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Property;

/**
 * AspectTrait Trait.
 */
trait AspectTrait
{

    /**
     * Returns whether aspects are supported.
     *
     * @return boolean
     */
    private function isAspected()
    {
        if ($this->systemType === 'user') {
            return false;
        }

        return true;

    }//end isAspected()


    /**
     * Get aspect to query properties with (read operation).
     *
     * If no aspect is set, the current aspect is used.
     *
     * @return array
     */
    private function getAspectRead()
    {
        $aspect = $this->getAspect();
        return $aspect;

    }//end getAspectRead()


    /**
     * Get aspect to query properties with (write operation).
     *
     * If no aspect is set, the master aspect is used.
     *
     * @return array
     */
    private function getAspectWrite()
    {
        $aspect = $this->getAspect();
        return $aspect;

    }//end getAspectWrite()


    /**
     * Get aspect to query properties with (write operation).
     *
     * If no aspect is set, an exception is thrown.
     *
     * @return array
     * @throws \Exception When no aspect.
     */
    private function getAspectRequired()
    {
        $aspect = $this->getAspect();
        if ($aspect === null) {
            throw new \Exception(_('Aspect is required'));
        } else {
            $aspect = self::getFlatAspectArray($aspect);
        }

        return $aspect;

    }//end getAspectRequired()


}//end class
