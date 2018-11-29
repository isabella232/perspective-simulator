<?php
/**
 * Request class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Requests;

/**
 * Request Class
 */
class Request
{


    /**
     * Checks if we are in author, for simulator always return true.
     *
     * @return boolean
     */
    public static function inAuthor()
    {
        return true;

    }//end inAuthor()


    /**
     * Returns the current deployment object.
     *
     * @return object
     * @throws \Exception When fails to create new deployment object.
     */
    public static function getDeployment()
    {
        try {
            $deploymentObject = new \PerspectiveSimulator\ObjectType\Deployment($GLOBALS['project']);
        } catch (\Exception $e) {
            throw new \Exception('Unable to create new deployment object');
        }

        return $deploymentObject;

    }//end getDeployment()


}//end class
