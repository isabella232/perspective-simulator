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
        $projects     = [];
        $simulatorDir = Libs\FileSystem::getSimulatorDir();
        if (is_dir($simulatorDir) === true) {
            $helper  = $this->getHelper('question');
            $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                'Simulator already installed. <comment>Re-installing it will delete all simulator data.</> Do you want to continue? (y/N) ',
                false
            );

            if ($helper->ask($input, $output, $confirm) === false) {
                return;
            }
        }

        Libs\FileSystem::delete($simulatorDir);
        Libs\FileSystem::mkdir($simulatorDir);
        Libs\FileSystem::mkdir($simulatorDir.'/sessions');
        touch($simulatorDir.'/error_log');

        $updateCommand = $this->getApplication()->find('simulator:update');
        $updateArgs    = [
            'command' => 'simulator:update',
        ];

        $updateInput = new \Symfony\Component\Console\Input\ArrayInput($updateArgs);
        $returnCode  = $updateCommand->run($updateInput, $output);

    }//end execute()


}//end class
