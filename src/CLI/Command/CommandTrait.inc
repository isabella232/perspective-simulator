<?php
/**
 * Command Trait for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

use \PerspectiveSimulator\Libs;

/**
 * Command Trait
 */
trait CommandTrait
{

    /**
     * An array of arguments for the action.
     *
     * @var array
     */
    private $args = [];


    /**
     * Gets an argument from the array.
     *
     * @param string $arg     The argument we want to get.
     * @param mixed  $default Default value if we want to set one.
     *
     * @return mixed
     */
    final public function getArg(string $arg, $default=null)
    {
        if (isset($this->args[$arg]) === true) {
            return $this->args[$arg];
        }

        return $default;

    }//end getArg()


}//end class
