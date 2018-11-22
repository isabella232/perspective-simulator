<?php
/**
 * Install class for Perspective Simulator CLI.
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

/**
 * Install Class
 */
class Install
{

    use CommandTrait;


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

    }//end __construct()


    /**
     * Runs the install command.
     *
     * @return void
     */
    public function install()
    {
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            Libs\FileSystem::mkdir($simulatorDir);
        } else {
            // If the simulator directory exists then we must have alreay installed.
            return;
        }

        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $GLOBALS['project'] = $project;

            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                if (is_dir($simulatorDir.'/'.$project) === false) {
                    Libs\FileSystem::mkdir($simulatorDir.'/'.$project);
                }

                $projectKey = \PerspectiveSimulator\Authentication::generateSecretKey();

                $storageDir = Libs\FileSystem::getStorageDir($project);
                if (is_dir($storageDir) === false) {
                    Libs\FileSystem::mkdir($storageDir);
                }

                \PerspectiveSimulator\API::installAPI($project);
                \PerspectiveSimulator\Queue\Queue::installQueues($project);
            }
        }//end foreach

    }//end install()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    final public function printHelp(string $filter=null)
    {
        $size = Terminal::getSize();
        Terminal::printHeader(
            Terminal::padText(_('Usage for: perspective [-i | --install]')),
            Terminal::STDERR
        );
        Terminal::printLine(_('    Use -i or --install to initialise the Perspective Simulator'));
        Terminal::printLine(
            sprintf(_('    %s this command can only be run once.'), Terminal::formatText(_('NOTE:'), ['bold']))
        );
        Terminal::printReset();

    }//end printHelp()


}//end class
