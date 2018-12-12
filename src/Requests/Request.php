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
     * Cahced object for the project this way we can set values with read/write disabled.
     *
     * @var array
     */
    private static $deployments = [];


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
    public static function getProjectInstance()
    {
        $project = $GLOBALS['project'];
        if (isset(self::$deployments[$project]) === true) {
            return self::$deployments[$project];
        }

        try {
            self::$deployments[$project] = new \PerspectiveSimulator\ObjectType\ProjectInstance($project);
        } catch (\Exception $e) {
            throw new \Exception('Unable to create new deployment object');
        }

        return self::$deployments[$project];

    }//end getProjectInstance()


}//end class
