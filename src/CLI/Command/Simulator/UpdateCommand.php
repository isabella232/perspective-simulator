<?php
/**
 * UpdateCommand class for Perspective Simulator CLI.
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
 * UpdateCommand Class
 */
class UpdateCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'simulator:update';

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
        $this->setDescription('Updates the simulator directory for use.');
        $this->setHelp('Updates the simulator directory for use with the latest API router and View Router.');

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
        $projects     = [];
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === false) {
            $helper  = $this->getHelper('question');
            $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                'Simualator isn\'t installed. Did you want to install it now instead?',
                true
            );

            if ($helper->ask($input, $output, $confirm) === true) {
                $installCommand = $this->getApplication()->find('simulator:install');
                $installArgs    = [
                    'command' => 'simulator:install',
                ];

                $installInput = new \Symfony\Component\Console\Input\ArrayInput($installArgs);
                $returnCode  = $installCommand->run($installInput, $output);
            }

            return;
        }

        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = Libs\FileSystem::listDirectory($projectPath, ['.json'], true, true, '/(composer)/');
        foreach ($projectDirs as $path) {
            if (strpos($path, 'vendor') !== false) {
                // Must be a dependancy so skip it as it will already have been loaded by the top level project.
                continue;
            }

            $section      = $output->section();
            $composerInfo = Libs\Util::jsonDecode(file_get_contents($path));
            if (isset($composerInfo['name']) === false) {
                // Invalid project so lets continue and print message.
                $section->writeln('<error>Unable to install project from path "'.$path.'" missing name key in composer.json</error>');
                continue;
            } else {
                $section->writeln('Installing project from "'.$path.'"');
            }

            $vendorProject               = $composerInfo['name'];
            $GLOBALS['projectNamespace'] = str_replace('/', '\\', $vendorProject);
            $GLOBALS['project']          = $vendorProject;
            $GLOBALS['projectPath']      = strtolower($vendorProject);

            $projects[$vendorProject] = str_replace('composer.json', 'src', $path);
            file_put_contents($simulatorDir.'/projects.json', Libs\Util::jsonEncode($projects));

            if (is_dir($simulatorDir.'/'.$GLOBALS['projectPath']) === false) {
                Libs\FileSystem::mkdir($simulatorDir.'/'.$GLOBALS['projectPath'], true);
            }

            $authFile = $simulatorDir.'/'.$GLOBALS['projectPath'].'/authentication.json';
            if (file_exists($authFile) === false) {
                $projectKey = \PerspectiveSimulator\Authentication::generateSecretKey();
            }

            $storageDir = Libs\FileSystem::getStorageDir($vendorProject);
            if (is_dir($storageDir) === false) {
                Libs\FileSystem::mkdir($storageDir);
            }

            \PerspectiveSimulator\API::installAPI($vendorProject);
            \PerspectiveSimulator\Queue\Queue::installQueues($vendorProject);
            \PerspectiveSimulator\View\View::installViews($vendorProject);

            // Combine theses so one loop for both.
            $requirements = [];
            if (isset($composerInfo['require']) === true) {
                $requirements = array_merge($requirements, $composerInfo['require']);
            }

            if (isset($composerInfo['require-dev']) === true) {
                $requirements = array_merge($requirements, $composerInfo['require-dev']);
            }

            foreach ($requirements as $requirement => $version) {
                $proj = str_replace('/', '\\', $requirement);
                \PerspectiveSimulator\API::installAPI($proj);
                \PerspectiveSimulator\Queue\Queue::installQueues($proj);
                \PerspectiveSimulator\View\View::installViews($proj);
            }

            $projectPath = str_replace('/composer.json', '', $path);
            chdir($projectPath);
            exec('composer install', $out, $ret);

            if (is_dir('./vendor') === false) {
                $section->overwrite('Installing project from "'.$path.'" <error>INCOMPLETE</error>');
                $style = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
                $style->error(
                    sprintf(
                        "\ncomposer install failed, please manually run composer install in \n\"%s\"\n to be able to use the simulator for this project.\n",
                        $projectPath
                    ),
                    null,
                    'error'
                );
            } else {
                $section->overwrite('Installing project from "'.$path.'" <info>DONE</info>');
            }
        }//end foreach
    }//end execute()


}//end class
