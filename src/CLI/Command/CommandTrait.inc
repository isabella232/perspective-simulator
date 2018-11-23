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
use \PerspectiveSimulator\CLI\Terminal;

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


    /**
     * This should be defined for each command.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    public function printHelp(string $filter=null)
    {
        Terminal::printHeader(
            Terminal::padText(_('No help found.')),
            Terminal::STDERR
        );

    }//end printHelp()


    /**
     * Prints the main help content for all commands.
     *
     * @param array  $actions Array of actions to print.
     * @param string $filter  Optionial filter key for actions.
     *
     * @return void
     */
    final public function printHelpToScreen(array $actions, string $filter=null)
    {
        $size = Terminal::getSize();
        foreach ($actions as $action => $details) {
            $command      = $details['action'];
            $commandArgs  = $details['arguments'];
            $descriptions = [
                'required' => [],
                'optional' => [],
            ];

            if (isset($commandArgs['required']) === true) {
                foreach ($commandArgs['required'] as $arg => $description) {
                    $command                   .= ' <'.Terminal::colourText($arg, 'yellow').'>';
                    $descriptions['required'][] = [
                        Terminal::colourText($arg, 'yellow'),
                        $description,
                    ];
                }
            }

            if (isset($commandArgs['optional']) === true) {
                foreach ($commandArgs['optional'] as $arg => $description) {
                    $command                   .= ' <'.Terminal::colourText($arg, 'cyan').'>';
                    $descriptions['optional'][] = [
                        Terminal::colourText($arg, 'cyan'),
                        $description,
                    ];
                }
            }

            $indent         = str_repeat(' ', 4);
            $optionMaxWidth = 25;

            Terminal::printLine($command);
            if (empty($descriptions['required']) === false) {
                Terminal::printLine();
                Terminal::printLine(Terminal::formatText(_('Required arguments:'), ['bold']));
            }

            foreach ($descriptions['required'] as $description) {
                Terminal::write(Terminal::padTo($indent.$description[0], $optionMaxWidth, ' '));
                Terminal::printLine(
                    Terminal::wrapText(
                        $description[1],
                        $size['cols'],
                        ' ',
                        $optionMaxWidth,
                        4,
                        false
                    )
                );
            }

            if (empty($descriptions['optional']) === false) {
                Terminal::printLine();
                Terminal::printLine(Terminal::formatText(_('Optional arguments:'), ['bold']));
            }

            foreach ($descriptions['optional'] as $description) {
                Terminal::write(Terminal::padTo($indent.$description[0], $optionMaxWidth, ' '));
                Terminal::printLine(
                    Terminal::wrapText(
                        $description[1],
                        $size['cols'],
                        ' ',
                        $optionMaxWidth,
                        4,
                        false
                    )
                );
            }

            Terminal::printLine();
        }//end foreach

        Terminal::printReset();

    }


}//end class
