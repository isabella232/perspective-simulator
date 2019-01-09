<?php
/**
 * Number property base class.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\PropertyType;

require_once dirname(__FILE__, 2).'/PropertyTrait.inc';
require_once dirname(__FILE__, 2).'/NumberTrait.inc';

use \PerspectiveAPI\Property\Types\Number as PerspectiveAPINumber;
use \PerspectiveSimulator\Property\PropertyTrait as PropertyTrait;
use \PerspectiveSimulator\Property\NumberTrait as NumberTrait;

/**
 * Integer Class.
 */
class Number extends PerspectiveAPINumber
{

    use PropertyTrait;
    use NumberTrait;

}//end class
