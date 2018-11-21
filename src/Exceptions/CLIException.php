<?php
/**
 * CLI Exception.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Exceptions;

use PerspectiveSimulator\CLI\Terminal;

/**
 * CLI Exception.
 */
class CLIException extends \Exception
{

    /**
     * The title of the error for pretty printing.
     *
     * @var string
     */
    protected $title = 'PerspectiveSimulator CLI Error';


    /**
     * Custom constructor to enforce error messages.
     *
     * @param string|array    $message  Custom message to display.
     * @param integer         $code     Error code.
     * @param \Exception|null $previous Previous exception.
     */
    public function __construct($message, $code=0, \Exception $previous=null)
    {
        parent::__construct($message, $code, $previous);

    }//end __construct()


    /**
     * Set a title for the error output.
     *
     * @param string $text The title of the error message.
     *
     * @return void
     */
    public function setTitle($text)
    {
        $this->title = $text;

    }//end setTitle()


    /**
     * Pretty print the exception to the console.
     *
     * @return void
     */
    public function prettyPrint()
    {
        $size = Terminal::getSize();

        Terminal::printHeader(
            Terminal::padText($this->title),
            Terminal::STDERR
        );

        Terminal::printError(
            Terminal::wrapText($this->message, $size['cols'])
        );

        Terminal::printReset();

        // Terminal::printError(
        //     sprintf(
        //         Terminal::RESET.'line: %s of %s',
        //         Terminal::colourText($this->line, 'yellow'),
        //         Terminal::colourText($this->file, 'cyan')
        //     )
        // );

        // Terminal::write(
        //     $this->getTraceAsString()
        // );

        Terminal::beep();

    }//end prettyPrint()


}//end class
