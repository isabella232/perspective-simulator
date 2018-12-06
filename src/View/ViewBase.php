<?php
/**
 * View Base class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\View;

/**
 * View Base Class
 */
abstract class ViewBase
{

    /**
     * Gets additional view data.
     *
     * @return array
     */
    abstract function getViewData();


}//end class
