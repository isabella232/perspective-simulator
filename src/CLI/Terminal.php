<?php
/**
 * Terminal class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI;

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\Exceptions\CLIException;

/**
 * CLI Terminal Class
 */
class Terminal
{
    // Output/Input pipes.
    const STDERR = 'stderr';
    const STDOUT = 'stdout';
    const STDIN  = 'stdin';

    // Formatting.
    const RESET          = "\033[0m";
    const RESTORE_CURSOR = "\033[u";
    const SAVE_CURSOR    = "\033[s";
    const CLEAR_LINE     = "\r\033[K";
    const BOLD           = 1;
    const BLINK          = 5;
    const DIM            = 2;
    const REVERSE_VIDEO  = 7;
    const ITALIC         = 3;
    const HIGHLIGHT      = 7;
    const UNDERLINE      = 4;
    const STRIKETHROUGH  = 9;

    // Colours.
    const BLACK   = 0;
    const RED     = 1;
    const GREEN   = 2;
    const YELLOW  = 3;
    const BLUE    = 4;
    const MAGENTA = 5;
    const CYAN    = 6;
    const WHITE   = 7;

    // Sensible defaults for padding & indentation.
    const DEFAULT_PAD_CHAR     = ' ';
    const DEFAULT_MARGIN_LEFT  = 4;
    const DEFAULT_MARGIN_RIGHT = 4;
    const DEFAULT_TERM_COLS    = 80;
    const DEFAULT_TERM_ROWS    = 40;


    /**
     * Stored size cache so we don't have to run tput each time.
     *
     * @var array
     */
    private static $sizeCache = [];

    /**
     * Colour text lookup
     *
     * @var array
     */
    private static $colours = [
        'black'   => self::BLACK,
        'red'     => self::RED,
        'green'   => self::GREEN,
        'yellow'  => self::YELLOW,
        'blue'    => self::BLUE,
        'magenta' => self::MAGENTA,
        'cyan'    => self::CYAN,
        'white'   => self::WHITE,
    ];

    /**
     * Format text lookup.
     *
     * @var array
     */
    private static $formats = [
        'bold'          => self::BOLD,
        'blink'         => self::BLINK,
        'dim'           => self::DIM,
        'rev'           => self::REVERSE_VIDEO,
        'italic'        => self::ITALIC,
        'highlight'     => self::HIGHLIGHT,
        'underline'     => self::UNDERLINE,
        'strikethrough' => self::STRIKETHROUGH,
    ];

    /**
     * Enable flag for coloured text.
     *
     * @var boolean
     */
    private static $coloursEnabled = true;

    /**
     * Total tally of output lines.
     *
     * @var integer
     */
    private static $linesCountActual = 0;


    /**
     * Get the terminal dimensions so that text can be formatted correctly to fit if necessary.
     *
     * @param integer $defaultWidth  The default width to use.
     * @param integer $defaultHeight The default height to use.
     *
     * @return array
     */
    public static function getSize($defaultWidth=self::DEFAULT_TERM_COLS, $defaultHeight=self::DEFAULT_TERM_ROWS)
    {
        if (empty(self::$sizeCache) === false) {
            return self::$sizeCache;
        }

        $size = [
            'cols'  => $defaultWidth,
            'lines' => $defaultHeight,
        ];

        foreach ($size as $part => $defaultValue) {
            $value = exec('tput '.$part.' 2>/dev/null');
            if (empty($value) === false) {
                $size[$part] = intval(exec('tput '.$part));
            }
        }

        self::$sizeCache = $size;

        return $size;

    }//end getSize()


    /**
     * Enable coloured output
     *
     * @return void
     */
    public static function enableColours()
    {
        self::$coloursEnabled = true;

    }//end enableColours()


    /**
     * Disable coloured output
     *
     * @return void
     */
    public static function disableColours()
    {
        self::$coloursEnabled = false;

    }//end disableColours()


    /**
     * Colourise text.
     *
     * @param string $text     Text to change.
     * @param string $fgColour Foreground colour.
     * @param string $bgColour Background colour.
     *
     * @return string
     */
    public static function colourText($text, $fgColour, $bgColour=null)
    {
        if (self::$coloursEnabled === false) {
            return $text;
        }

        $fgCode = ['3'.self::$colours[$fgColour]];
        if ($bgColour !== null) {
            $text = self::getFormattedText(
                $text,
                array_merge(
                    $fgCode,
                    ['4'.self::$colours[$bgColour]]
                )
            );
        } else {
            $text = self::getFormattedText($text, $fgCode);
        }

        return $text.self::RESET;

    }//end colourText()


    /**
     * Format text.
     *
     * @param string       $text    Text to change.
     * @param string|array $formats Existing format key or array of keys.
     *
     * @return string
     */
    public static function formatText($text, $formats=[])
    {
        $formats = (array) $formats;
        $codes   = [];

        foreach ($formats as $format) {
            if (isset(self::$formats[$format]) === true) {
                $codes[] = self::$formats[$format];
            }
        }

        return self::getFormattedText($text, $codes);

    }//end formatText()


    /**
     * Get formatted text for the console.
     *
     * @param string $text  Text to escape.
     * @param array  $codes Valid terminfo codes.
     *
     * @return string
     */
    private static function getFormattedText($text, array $codes=[])
    {
        // Escape %.
        $text = preg_replace('/%/m', '%%', $text);
        return sprintf("\033[%sm".$text, join(';', $codes));

    }//end getFormattedText()


    /**
     * Print Header text
     *
     * @param string $header Header to send.
     * @param string $pipe   Output channel.
     *
     * @return void
     */
    public static function printHeader($header, $pipe=self::STDOUT)
    {
        $size    = self::getSize();
        $divider = str_repeat('-', $size['cols']);

        self::printLine($divider."\n", $pipe);
        self::printLine($header, $pipe);
        self::printLine("\n".$divider, $pipe);

    }//end printHeader()


    /**
     * Cause the terminal to beep.
     *
     * @return void
     */
    public static function beep()
    {
        echo "\x7";

    }//end beep()


    /**
     * Print to STDERR
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public static function printError($msg)
    {
        self::printLine($msg, false, self::STDERR);

    }//end printError()


    /**
     * Print text with a newline character.
     *
     * @param string $msg     Message to send.
     * @param string $counted Whether the lines should be counted.
     * @param string $pipe    Output channel.
     *
     * @return array
     */
    public static function printLine($msg='', $counted=true, $pipe=self::STDOUT)
    {
        return self::write($msg, $counted, $pipe, true);

    }//end printLine()


    /**
     * Prints a reset format char.
     *
     * @return void
     */
    public static function printReset()
    {
        return self::write(self::RESET, false, self::STDOUT, false, false);

    }//end printReset()


    /**
     * Print an array as a table with keys as headings.
     *
     * @param array  $table Tabular array of data.
     * @param string $pipe  Output channel.
     *
     * @return void
     */
    public static function printTable(array $table, $pipe=self::STDOUT)
    {
        $vDelim     = ' | ';
        $hDelim     = '-';
        $first      = array_slice($table, 0, 1);
        $keys       = array_keys(array_pop($first));
        $sizeLookup = [];

        foreach ($table as $row) {
            foreach ($row as $index => $column) {
                $size = strlen($column);

                if (isset($sizeLookup[$index]) === false) {
                    $sizeLookup[$index] = strlen($index);
                }

                if ($size > $sizeLookup[$index]) {
                    $sizeLookup[$index] = $size;
                }
            }
        }

        $head = '';
        foreach ($keys as $index => $key) {
            $head .= self::padTo($key, $sizeLookup[$key]).$vDelim;
        }

        if ($index === 0) {
            self::printLine(self::padTo('', $sizeLookup[$key], $hDelim));
        }

        self::printLine(str_repeat($hDelim, strlen($head)), false, $pipe);
        self::printLine($head, false, $pipe);
        self::printLine(str_repeat($hDelim, strlen($head)), false, $pipe);

        foreach ($table as $row) {
            $rowText = '';

            // Print value.
            foreach ($row as $key => $column) {
                if (is_string($column) === false) {
                    $column = var_export($column, true);
                }

                $rowText .= self::padTo($column, $sizeLookup[$key]).$vDelim;
            }

            self::printLine($rowText, false, $pipe);
        }

        self::printLine(str_repeat($hDelim, strlen($head)), false, $pipe);

    }//end printTable()


    /**
     * Print Text to the console.
     *
     * @param string  $msg     Message to output.
     * @param boolean $counted Whether the lines should be counted.
     * @param string  $pipe    Output channel.
     * @param boolean $newline Output a newline character after the content.
     * @param boolean $reset   Set to FALSE to prevent outputting a reset character.
     *
     * @return null|array If lines are written returns [lineCountStart, lineCountEnd] otherwise null.
     */
    public static function write(
        $msg,
        $counted=true,
        $pipe=self::STDOUT,
        $newline=false,
        $reset=true
    ) {
        $nextLine = (self::$linesCountActual + 1);
        self::incrementLineCounter($msg);

        $out = fopen('php://'.$pipe, 'w');
        fwrite($out, $msg);
        if ($newline === true) {
            self::incrementLineCounter("\n");
            fwrite($out, "\n");
        }

        if ($reset === true) {
            fwrite($out, self::RESET);
        }

        fclose($out);

        return [
            $nextLine,
            self::$linesCountActual,
        ];

    }//end write()


    /**
     * Increment the internal line counter.
     *
     * @param string  $msg The message that will be output.
     * @param integer $add Any additional lines to add to the counter.
     *
     * @return void
     */
    private static function incrementLineCounter($msg, $add=0)
    {
        $newlines  = $add;
        $newlines += substr_count($msg, "\n");
        self::$linesCountActual += $newlines;

    }//end incrementLineCounter()


    /**
     * Pads text with left and right margins.
     *
     * @param string  $text        Text to format.
     * @param string  $padChar     The pad character to use.
     * @param integer $marginLeft  Repetition count for left margin.
     * @param integer $marginRight Repetition count for right margin.
     *
     * @return string
     */
    public static function padText(
        $text,
        $padChar=self::DEFAULT_PAD_CHAR,
        $marginLeft=self::DEFAULT_MARGIN_LEFT,
        $marginRight=self::DEFAULT_MARGIN_RIGHT
    ) {
        return str_repeat($padChar, $marginLeft).$text.str_repeat($padChar, $marginRight);

    }//end padText()


    /**
     * Wrap text with newlines if over a defined maximum length.
     *
     * @param string  $text            Text to format.
     * @param integer $maxSize         Maximum size to wrap.
     * @param string  $padChar         The pad character to use.
     * @param integer $marginLeft      Repetition count for left margin.
     * @param integer $marginRight     Repetition count for right margin.
     * @param boolean $indentFirstLine Whether to indent the first line.
     *
     * @return string
     */
    public static function wrapText(
        $text,
        $maxSize=self::DEFAULT_TERM_COLS,
        $padChar=self::DEFAULT_PAD_CHAR,
        $marginLeft=self::DEFAULT_MARGIN_LEFT,
        $marginRight=self::DEFAULT_MARGIN_RIGHT,
        $indentFirstLine=true
    ) {
        $lenPadChar    = strlen($padChar);
        $actualMaxSize = ($maxSize - ($marginLeft * $lenPadChar) - ($marginRight * $lenPadChar));
        $lenText       = strlen($text);

        $lines  = explode("\n", $text);
        $padded = [];

        foreach ($lines as $line) {
            $chunked = explode("\n", wordwrap($line, $actualMaxSize, "\n"));

            foreach ($chunked as $index => $chunk) {
                if ($index === 0 && $indentFirstLine === false) {
                    $padded[] = self::padText($chunk, $padChar, 0, $marginRight);
                } else {
                    $padded[] = self::padText($chunk, $padChar, $marginLeft, $marginRight);
                }
            }
        }

        return join("\n", $padded);

    }//end wrapText()


    /**
     * Pad text to a maximum width.
     *
     * @param string  $text      String to pad.
     * @param integer $maxWidth  Maximum width (or string length if it exceeds width).
     * @param string  $padChar   The pad character to use.
     * @param string  $direction The direction to pad (left|right|center).
     *
     * @return string
     */
    public static function padTo(
        $text,
        $maxWidth,
        $padChar=self::DEFAULT_PAD_CHAR,
        $direction='right'
    ) {
        $marginLeft  = 0;
        $marginRight = 0;
        $length      = strlen(self::stripControlChars($text));

        if ($length > $maxWidth) {
            $maxWidth = $length;
        }

        switch ($direction) {
            case 'center':
                $marginLeft  = ceil(($maxWidth - $length) / 2);
                $marginRight = floor(($maxWidth - $length) / 2);
            break;

            case 'left':
                $marginLeft = ($maxWidth - $length);
            break;

            default:
            case 'right':
                $marginRight = ($maxWidth - $length);
            break;
        }

        return self::padText($text, $padChar, $marginLeft, $marginRight);

    }//end padTo()


    /**
     * Strip out control characters (e.g. colours)
     *
     * @param string $text Text to modify.
     *
     * @return string
     */
    public static function stripControlChars($text)
    {
        return preg_replace('/[[:^print:]]/', '', $text);

    }//end stripControlChars()


}//end class
