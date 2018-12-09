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
            // If the simulator directory exists then we must have alreay installed. So lets just rebake the API and Queues.
            $this->reInit();
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
                \PerspectiveSimulator\View\View::installViews($project);
            }
        }//end foreach

    }//end install()


    /**
     * Updates the API Router and Queues. Run when the simulator has already been installed and we are rerunning the
     * -i or --install command.
     *
     * @return void
     */
    private function reInit()
    {
        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $GLOBALS['project'] = $project;

            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.') {
                \PerspectiveSimulator\API::installAPI($project);
                \PerspectiveSimulator\Queue\Queue::installQueues($project);
                \PerspectiveSimulator\View\View::installViews($project);
            }
        }//end foreach

    }//end reInit()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    final public function printHelp(string $filter=null)
    {
        $actions = [
            '-i'        => [
                'action'      => 'perspective -i',
                'description' => 'Installs the simulator.',
                'arguments'   => [],
            ],
            '--install' => [
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
                    'Usage for: perspective -i|--install'
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
