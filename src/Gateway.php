<?php
/**
 * Gateway class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

use PerspectiveSimulator\Libs;

/**
 * Gateway class.
 */
class Gateway
{

    /**
     * The current user.
     *
     * @var object
     */
    private static $url = 'http://127.0.0.1:3456';


    /**
     * Construct function for a Gateway object.
     *
     * @return void
     */
    final public function __construct()
    {

    }//end __construct()


    /**
     * Gets the gateway url.
     *
     * @return string
     */
    public function getGatewayURL()
    {
        return self::$url;

    }//end getGateway()


    /**
     * Sets the Gateway key.
     *
     * @param string $key The Gateway API's key.
     *
     */
    public function setGatewayKey(string $key)
    {
        $file = Libs\FileSystem::getExportDir().'/.apiKey';
        file_put_contents($file, $key);

    }//end setGatewayKey()


    public function getGatewayKey()
    {
        $retVal = false;
        $file   = Libs\FileSystem::getExportDir().'/.apiKey';
        if (file_exists($file) === true) {
            $retVal = file_get_contents($file);
        }

        return $retVal;

    }//end getGatewayKey()


}//end class
