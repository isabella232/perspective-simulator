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
        $genAuth      = false;
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            $genAuth = true;
            Libs\FileSystem::mkdir($simulatorDir);
        }

        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $vendor) {
            $vendorDirs = scandir($projectPath.$vendor);
            foreach ($vendorDirs as $project) {
                if (is_dir($projectPath.$vendor) === true && $vendor[0] !== '.') {
                    $vendorProject      = $vendor.'/'.$project;
                    $GLOBALS['project'] = $vendorProject;

                    $path = $projectPath.$vendor.'/'.$project;
                    if (is_dir($path) === true && $project[0] !== '.') {
                        if (is_dir($simulatorDir.'/'.$vendor.'/'.$project) === false) {
                            Libs\FileSystem::mkdir($simulatorDir.'/'.$vendor.'/'.$project, true);
                        }

                        if ($genAuth === true) {
                            $projectKey = \PerspectiveSimulator\Authentication::generateSecretKey();
                        }

                        $storageDir = Libs\FileSystem::getStorageDir($vendorProject);
                        if (is_dir($storageDir) === false) {
                            Libs\FileSystem::mkdir($storageDir);
                        }

                        \PerspectiveSimulator\API::installAPI($vendorProject);
                        \PerspectiveSimulator\Queue\Queue::installQueues($vendorProject);
                        \PerspectiveSimulator\View\View::installViews($vendorProject);

                        $composer = $path.'/composer.json';
                        if (file_exists($composer) === true) {
                            $composerContents = Libs\Util::jsonDecode(file_get_contents($composer));
                            if (isset($composerContents['require']) === true) {
                                foreach ($composerContents['require'] as $requirement => $version) {
                                    $proj = str_replace('/', '\\', $requirement);
                                    \PerspectiveSimulator\API::installAPI($proj);
                                    \PerspectiveSimulator\Queue\Queue::installQueues($proj);
                                    \PerspectiveSimulator\View\View::installViews($proj);
                                }
                            }

                            if (isset($composerContents['require-dev']) === true) {
                                foreach ($composerContents['require-dev'] as $requirement => $version) {
                                    $proj = str_replace('/', '\\', $requirement);
                                    \PerspectiveSimulator\API::installAPI($proj);
                                    \PerspectiveSimulator\Queue\Queue::installQueues($proj);
                                    \PerspectiveSimulator\View\View::installViews($proj);
                                }
                            }
                        }
                    }
                }//end if
            }//end foreach
        }//end foreach

    }//end execute()


}//end class
