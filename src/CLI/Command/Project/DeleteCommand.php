<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Project;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'project:delete';

    /**
     * The direcrtory where the export stores the data.
     *
     * @var string
     */
    private $storeDir = null;


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Deletes a Project from the export.');
        $this->setHelp('Deletes a Project.');
        $this->addArgument('namespace', InputArgument::REQUIRED);

    }//end configure()


    /**
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $exportDir      = Libs\FileSystem::getExportDir();
        $this->storeDir = $exportDir.'/projects/';
        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

        $helper = $this->getHelper('question');

        $namespace = ($input->getArgument('namespace') ?? null);
        if (empty($input->getArgument('namespace')) === true) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter a Project namespace: ');
            $namespace  = $helper->ask($input, $output, $question);
            $input->setArgument('namespace', $namespace);
        }

        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion('This will delete the project: "'.$namespace.'"', false);
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
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
        $output->writeln([
            'Deleting project',
            '================================================',
            '',
        ]);

        try {
            $namespace = $input->getArgument('namespace');
            $projectDir = Libs\FileSystem::getExportDir().'/projects/'.$namespace;
            if (is_dir($projectDir) === false) {
                throw new \Exception(sprintf('Project (%s) doesn\'t exist', $namespace));
            }

            Libs\FileSystem::delete($projectDir);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

        $output->writeln([
            'Project successfuly deleted: '.$namespace,
            '',
        ]);

    }//end execute()


}//end class
