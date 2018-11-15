<?php
/**
 * Util class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Storage\StorageFactory;

/**
 * Util class
 */
class Util
{


    /**
     * Prints data in JSON format.
     *
     * @param mixed   $value   The value we want in JSON format.
     * @param integer $options Bitmask of JSON_* constants.
     *
     * @return string
     */
    public static function jsonEncode($value, int $options=128)
    {
        return json_encode($value, $options);

    }//end jsonEncode()


    /**
     * Takes a JSON encoded string and converts it into a PHP variable.
     *
     * @param mixed   $value Value to decode.
     * @param boolean $assoc When TRUE, returned objects will be converted into associative arrays.
     *
     * @return mixed
     */
    public static function jsonDecode($value, bool $assoc=true)
    {
        return json_decode($value, $assoc);

    }//end jsonDecode()


}//end class
