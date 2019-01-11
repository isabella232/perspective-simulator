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
class CleanCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'simulator:clean';

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
        $this->setDescription('Cleans the simulator directory for a project.');
        $this->setHelp('Cleans the simulator directory for a project.');

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Flag to clean all projects data, this overides --project flag.',
            null
        );

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
        // Clean task doesn't need to write any data so just disable writes.
        \PerspectiveSimulator\Bootstrap::disableWrite();

        $all = ($input->getOption('all') ?? false);
        if ($all === true) {
            $simDir   = Libs\FileSystem::getSimulatorDir();
            $projects = Libs\Util::jsonDecode(file_get_contents($simDir.'/projects.json'));

            $this->storeDir = [];
            foreach ($projects as $project => $path) {
                $this->storeDir[] = $simDir.'/'.$project.'/storage';
            }

        } else {
            $this->inProject($input, $output);

            $simDir         = Libs\FileSystem::getSimulatorDir();
            $this->storeDir = $simDir.'/'.$GLOBALS['projectPath'].'/storage';
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
        try {
            if (is_array($this->storeDir) === true) {
                foreach ($this->storeDir as $dir) {
                    if (is_dir($dir) === true) {
                        Libs\FileSystem::delete($dir);
                        Libs\FileSystem::mkdir($dir);
                    }
                }
            } else if (is_dir($this->storeDir) === true) {
                Libs\FileSystem::delete($this->storeDir);
                Libs\FileSystem::mkdir($this->storeDir);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
