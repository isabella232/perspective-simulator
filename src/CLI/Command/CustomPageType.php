<?php
/**
 * CustomPageType class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

require_once dirname(__FILE__).'/CustomTypesTrait.inc';

use \PerspectiveSimulator\Libs;

/**
 * CustomPageType Class
 */
class CustomPageType
{
    use CustomTypesTrait;


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
        $this->storeDir     = $projectDir.'/CustomTypes/Page/';
        $this->type         = 'custompagetype';
        $this->readableType = 'Custom Page Type';
        $this->namespace    = $GLOBALS['project'].'\\CustomTypes\\Page';
        $this->extends      = 'Page';
        $this->setArgs($action, $args);

    }//end __construct()


}//end class
