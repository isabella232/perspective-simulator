<?php
/**
 * AddCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Stores;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class RenameCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:rename';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $type = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';

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
        $this->setDescription('Renames store in a project.');
        $this->setHelp('Renames store in a project.');
        $this->addArgument('type', InputArgument::REQUIRED, 'The type of the store, eg, data or user.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the store being renamed.');
        $this->addArgument('newName', InputArgument::REQUIRED, 'The new name of the store.');

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
        $this->inProject($input, $output);

        $helper    = $this->getHelper('question');
        $storeType = $input->getArgument('type');
        if (empty($input->getArgument('type')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which store type you are wanting to create.',
                ['data', 'user'],
                0
            );

            $storeType = $helper->ask($input, $output, $question);
            $input->setArgument('type', $storeType);
            $output->writeln('You have just selected: '.$storeType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($storeType) === 'data') {
            $this->storeDir     = $projectDir.'/Stores/Data/';
            $this->readableType = 'Data Store';
            $this->type         = 'DataStore';
        } else if (strtolower($storeType) === 'user') {
            $this->storeDir     = $projectDir.'/Stores/User/';
            $this->readableType = 'User Store';
            $this->type         = 'UserStore';
        }

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end interact()


    /**
     * Validates the name of the store.
     *
     * @param string $name Name of the data store.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateStoreName(string $name)
    {
        if ($name === null) {
            $eMsg = sprintf('%s name is required.', $this->readableType);
            throw new CLIException($eMsg);
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s name provided', $this->readableType);
            throw new CLIException($eMsg);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        $dirs       = glob($this->storeDir.'*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $storeName = strtolower(basename($dir));
            if (strtolower($name) === $storeName) {
                $eMsg = sprintf('%s name is already in use', $this->readableType);
                throw new CLIException($eMsg);
            }
        }

        return $name;

    }//end validateStoreName()


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
            $oldName = $input->getArgument('name');
            $newName = $input->getArgument('newName');

            $this->validateStoreName($newName);
            $oldDir = $this->storeDir.$oldName;
            $newDir = $this->storeDir.$newName;
            Libs\Git::move($oldDir, $newDir);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
