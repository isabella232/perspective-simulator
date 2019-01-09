<?php
/**
 * File property base class.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\PropertyType;

require_once dirname(__FILE__, 2).'/PropertyTrait.inc';
require_once dirname(__FILE__, 2).'/FileTrait.inc';

use \PerspectiveAPI\Property\Types\File as PerspectiveAPIFile;
use \PerspectiveSimulator\Property\PropertyTrait as PropertyTrait;
use \PerspectiveSimulator\Property\FileTrait as FileTrait;

/**
 * File Class.
 */
class File extends PerspectiveAPIFile
{

    use PropertyTrait;
    use FileTrait;

}//end class
