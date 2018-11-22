<?php
/**
 * Project Property class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

require_once dirname(__FILE__).'/PropertyTrait.inc';

use \PerspectiveSimulator\Libs;

/**
 * ProjectProperty Class
 */
class ProjectProperty
{
    use PropertyTrait;


    /**
     * Constructor function.
     *
     * @param string $action The action we are going to perfom.
     * @param array  $args   An array of arguments to be used.
     *
     * @return void
     */
    public function __construct(string $action, array $args)
    {
        $projectDir         = Libs\FileSystem::getProjectDir();
        $this->storeDir     = $projectDir.'/Properties/Project/';
        $this->type         = 'project';
        $this->readableType = 'Project';
        $this->setArgs($action, $args);

    }//end __construct()


}//end class
