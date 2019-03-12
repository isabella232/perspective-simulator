<?php
/**
 * AddCommand class for Perspective Simulator CLI.
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
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class ImportCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'simulator:import';


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Imports the simulator data for a project.');
        $this->setHelp('Imports the simulator data for a project.');
        $this->addArgument('importFile', InputArgument::REQUIRED, 'Import file name to run.');

    }//end configure()


    /**
     * Make sure that the system name is set.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $project = ($input->getOption('project') ?? null);
        if ($project === null) {
            $this->inProject($input, $output);
        } else {
            $project = str_replace('/', '\\', $project);
            \PerspectiveSimulator\Bootstrap::load($project);
            $input->setOption('project', str_replace('\\', '/', $project));
        }

    }//end interact()


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
        $projectDir = Libs\FileSystem::getProjectDir();
        $importDir = dirname($projectDir).'/import';
        $importFileName = $input->getArgument('importFile');
        $importFilePath = $importDir.'/'.$importFileName.'.php';
        if (file_exists($importFilePath) === false) {
            throw new \Exception(sprintf('Import file does not exist: %s', $importFilePath));
        }


        include_once $importFilePath;
        $importClassname = '\\'.$GLOBALS['projectNamespace'].$importFileName;
        $importObject    = new $importClassname($GLOBALS['projectNamespace']);

        // Clean task doesn't need to write any data so just disable writes.
        \PerspectiveSimulator\Bootstrap::disableWrite();
        $project          = $input->getOption('project');
        $cleanCommand     = $this->getApplication()->find('simulator:clean');
        $cleanCommandArgs = [
            'command'   => 'simulator:clean',
            '--project' => $project,
        ];

        $cleanCommandInput = new \Symfony\Component\Console\Input\ArrayInput($cleanCommandArgs);
        $returnCode     = $cleanCommand->run($cleanCommandInput, $output);
        if ($returnCode !== 0) {
            throw new \Exception(sprintf('Failed to clean the project: %s', $project));
        }

        \PerspectiveSimulator\Bootstrap::enableWrite();
        $importObject->import();

    }//end execute()


}//end class
