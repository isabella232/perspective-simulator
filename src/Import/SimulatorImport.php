<?php
/**
 * Simulator Test class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Import;

use \PerspectiveSimulator\Bootstrap;

/**
 * SimulatorImport class
 */
abstract class SimulatorImport
{

    protected $api = null;

    public function __construct(string $projectNamespace)
    {
        \PerspectiveSimulator\Bootstrap::enableWrite();
        \PerspectiveSimulator\Bootstrap::disableNotifications();
        \PerspectiveSimulator\Bootstrap::load(rtrim($projectNamespace, '\\'));
        $apiClassname = '\\'.$projectNamespace.'API';
        $this->api = new $apiClassname();

    }//end __construct()

    abstract public function import();


}//end class
