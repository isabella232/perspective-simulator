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


    /**
     * Fromats code lines.
     *
     * @param integer $level The indentation level.
     * @param string  $line  The line of code.
     *
     * @return string
     */
    public static function printCode(int $level, string $line)
    {
        $indent = function ($lvl) {
            return str_repeat(' ', ($lvl * 4));
        };

        return $indent($level).$line."\n";

    }//end printCode()


    /**
     * Only checks the letters used in ID strings.
     *
     * @param string  $stringid The id of being used.
     * @param boolean $allowDot Flag to allow . or not.
     *
     * @return boolean
     */
    public static function isValidStringid(string $stringid, bool $allowDot=false)
    {
        $re = 'a-zA-Z0-9\_\-';
        if ($allowDot === true) {
            // Allow . for subproperty.
            $re .= '\.';
        }

        $re = '/[^'.$re.']/';
        if (empty($stringid) === true
            || preg_match($re, $stringid) !== 0
            || strpos($stringid, chr(0)) !== false
        ) {
            return false;
        }

        // Check a stringid must have at least 1 non numeric character in it.  We index arrays by stringid and it
        // can convert string numbers into integer array keys and this has array merge implications.
        if (preg_match('/[a-zA-Z\_\-\.]/', $stringid) === 0) {
            return false;
        }

        // Check first letter.
        if (preg_match('/^[a-zA-Z\_\-]/', $stringid) === 0) {
            return false;
        }

        return true;

    }//end isValidStringid()


}//end class
