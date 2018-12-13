<?php
/**
 * InstallCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Simulator;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * InstallCommand Class
 */
class InstallCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'simulator:install';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Installs the simulator directory for use.');
        $this->setHelp('Installs the simulator directory for use.');

    }//end configure()


    /**
     * Executes the create new project command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            Libs\FileSystem::mkdir($simulatorDir);
        } else {
            // If the simulator directory exists then we must have alreay installed. So lets just rebake the API and Queues.
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

    }//end execute()


}//end class
