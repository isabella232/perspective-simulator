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


    /**
     * Returns true if the string is suitable for PHP class name.
     *
     * @param string $string The string to test.
     *
     * @return boolean
     */
    public static function isPHPClassString(string $string)
    {
        // Regex ref: http://php.net/manual/en/language.oop5.basic.php
        $re = '^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$';
        $re = '/'.$re.'/';
        if (empty($string) === true
            || preg_match($re, $string) !== 1
            || strpos($string, chr(0)) !== false
        ) {
            return false;
        }

        return true;

    }//end isPHPClassString()


    /**
     * Returns the default class content used from creating a new Custom Type and App PHP Class.
     *
     * @return string
     */
    public static function getDefaultPHPClass()
    {
        $defaultClass = '<?php
namespace NAMESPACE;

/**
 * Class comment goes here.
 */
class CLASS_NAME CLASS_EXTENDS
{

}//end class
';
        return $defaultClass;

    }//end getDefaultPHPClass()


    /**
     * Updates changes in PHP Class code for App and Custom Types when namespace or class name needs to be updated.
     *
     * @param string $sourceCode The original source code to be updated.
     * @param array  $changeData Array of old and new values to be used.
     * @param string $typeChange What we are going to changeeg, classname or namespace. or extends
     *
     * @return string
     */
    public static function updatePHPCode($sourceCode, $changeData , $typeChange='classname')
    {
        $newSourceCode = $sourceCode;
        if ($typeChange === 'classname') {
            $re            = '/(class|trait|interface)(\s)+'.$changeData['oldClassName'].'(\s)*/';
            $newSourceCode = preg_replace($re, '\1\2'.$changeData['newClassName'].'\3', $sourceCode);
        } else if ($typeChange === 'namespace') {
            $re            = '/(namespace\s)([\s\w\\\]|[\#\/\w\.])+([\;]{1})/';
            $newSourceCode = preg_replace($re, '\1'.$changeData['newNamespace'].'\3', $sourceCode);
        } else if ($typeChange === 'extends') {
            $re            = '/(extends\s)[\s\w]+([\s]{1})/';
            $newSourceCode = preg_replace($re, '\1'.$changeData['newExtends'].'\2', $sourceCode);
        }

        return $newSourceCode;

    }//end updatePHPCode()


}//end class
