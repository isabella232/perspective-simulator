<?php
/**
 * Termianl Prompt class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI;

use \PerspectiveSimulator\Libs;

/**
 * Prompt Class.
 */
class Prompt
{


    /**
     * Format a message for output.
     *
     * @param string $msg     The message/question asked.
     * @param string $default Any default value.
     * @param string $answer  The users answer to format.
     *
     * @return string Formatted message for the console.
     */
    private static function formatMessage($msg, $default='', $answer='')
    {
        if (empty($answer) === true) {
            $mark = Terminal::colourText('?', 'yellow');
        } else {
            $mark = Terminal::colourText('*', 'green');
        }

        $msg = '['.$mark.'] '.$msg.': ';
        if (empty($default) === false) {
            $msg .= $default.' ';
        }

        if (empty($answer) === false) {
            $msg .= Terminal::colourText($answer, 'green');
        }

        return $msg;

    }//end formatMessage()


    /**
     * Print the answer to a prompt.
     *
     * @param string $msg     The message/question asked.
     * @param string $default Any default value.
     * @param string $answer  The users answer to format.
     *
     * @return void
     */
    private static function printAnswer($msg, $default='', $answer='')
    {
        Terminal::clear();
        Terminal::printLine(self::formatMessage($msg, $default, $answer), false);

    }//end printAnswer()


    /**
     * Print a validation error.
     *
     * @param string $msg Message to display.
     *
     * @return void
     */
    private static function printValidationError($msg)
    {
        Terminal::printLine(Terminal::colourText('>>', 'red').' '.$msg);

    }//end _printValidationError()


    /**
     * Prompt the user for text input.
     *
     * @param string $message The message to display.
     * @param string $default The default value to use.
     *
     * @return string
     */
    public static function textInput($message, $default='')
    {
        $defaultMessage = '';
        if (empty($default) === false) {
            $defaultMessage = '('.$default.')';
        }

        $formatted = self::formatMessage($message, $defaultMessage);
        $input     = trim(Terminal::readline($formatted));

        if (empty($input) === true) {
            $input = $default;
        }

        self::printAnswer($message, $default, $input);

        return $input;

    }//end textInput()


    /**
     * Output a confirmation style prompt.
     *
     * @param string  $message The message to display.
     * @param boolean $default When TRUE 'Yes' will be the default, otherwise 'No'.
     *
     * @return boolean
     */
    public static function confirm($message, $default=false)
    {
        $answer  = 'No';
        $options = '(y/N)';
        if ($default === true) {
            $answer  = 'Yes';
            $options = '(Y/n)';
        }

        $size = Terminal::getSize();

        $formatted = self::formatMessage($message, $options);
        $formatted = Terminal::wrapText(
            $formatted,
            $size['cols'],
            ' ',
            0,
            4
        );
        $input     = strtoupper(trim(Terminal::readline($formatted)));

        if ($input === 'Y') {
            $answer = 'Yes';
        } else if ($input === 'N') {
            $answer = 'No';
        }

        self::printAnswer($message, $options, $answer);

        if ($answer === 'Yes') {
            return true;
        } else {
            return false;
        }

    }//end confirm()


    /**
     * An interactive numbered option list.
     *
     * @param string $message    The message to display.
     * @param array  $options    Option list with 'value' and 'description' for items.
     * @param string $inputText  The input text to display.
     * @param string $answerText The answer to display.
     * @param string $errorText  The error to display.
     *
     * @return string The selected option value.
     */
    public static function optionList(
        string $message,
        array $options,
        string $inputText=null,
        string $answerText=null,
        string $errorText=null
    ) {
        // No need for a prompt if there is only a single value in the list.
        if (count($options) === 1) {
            return $options[0];
        }

        $size = Terminal::getSize();

        Terminal::printLine(self::formatMessage($message));
        $maxWidth = strlen((string) count($options));
        foreach ($options as $index => $option) {
            $selected = '    '.Terminal::padTo(($index + 1).'.', ($maxWidth + 2));
            Terminal::printLine($selected.$option);
        }

        if ($inputText === null) {
            $inputText = _('Select a number (default: 1): ');
        }

        $input = trim(Terminal::readline($inputText));

        if (empty($input) === true) {
            $input = 1;
        }

        $selectedIndex = ((int) $input - 1);

        if (isset($options[$selectedIndex]) === true) {
            if ($answerText === null) {
                $answerText = $message;
            }

            self::printAnswer($answerText, '', $options[$selectedIndex]);
            return $options[$selectedIndex];
        } else {
            if ($errorText === null) {
                $errorText = _('That option is not valid.');
            }

            Terminal::clear();
            self::printValidationError($errorText);
            return self::optionList($message, $options, $inputText, $answerText, $errorText);
        }

        return null;

    }//end optionList()


}//end class
