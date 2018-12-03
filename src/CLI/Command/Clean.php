<?php
/**
 * Clean class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

require_once dirname(__FILE__).'/CommandTrait.inc';

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\CLI\Terminal;
use \PerspectiveSimulator\Exceptions\CLIException;

/**
 * Clean Class
 */
class Clean
{
    use CommandTrait;

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


    /**
     * Constructor function.
     *
     * @param string $action The action we are going to perfom.
     * @param array  $args   An array of arguments to be used.
     *
     * @return void
     */
    public function __construct(string $action, array $args)
    {
        $simDir         = Libs\FileSystem::getSimulatorDir();
        $this->storeDir = $simDir.'/'.$GLOBALS['project'].'/storage';
        $this->setArgs($action, $args);

    }//end __construct()


    /**
     * Sets the args array.
     *
     * @param string $action Action that will be performed later.
     * @param array  $args   The arguments to be set.
     *
     * @return void
     */
    private function setArgs(string $action, array $args)
    {
        $this->args = $args;

    }//end setArgs()


    /**
     * Adds a new specification file, changes to this will affect update when path is given.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function clean()
    {
        try {
            if (is_dir($this->storeDir) === true) {
                Libs\FileSystem::delete($this->storeDir);
                Libs\FileSystem::mkdir($this->storeDir);
            }
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end add()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    public function printHelp(string $filter=null)
    {
        $actions = [
            '-c'        => [
                'action'      => 'perspective -i',
                'description' => 'Installs the simulator.',
                'arguments'   => [],
            ],
            '--clean' => [
                'action'      => 'perspective --install',
                'description' => 'Installs the simulator.',
                'arguments'   => [],
            ],
        ];

        if ($filter !== null) {
            $actions = array_filter(
                $actions,
                function ($a) use ($filter) {
                    return $a === $filter;
                },
                ARRAY_FILTER_USE_KEY
            );

            Terminal::printLine(
                Terminal::padText(
                    'Usage for: '.$actions[$filter]['action']
                )
            );
        } else {
            Terminal::printLine(
                Terminal::padText(
                    'Usage for: perspective -c|--clean'
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
