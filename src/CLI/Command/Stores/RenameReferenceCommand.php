<?php
/**
 * RenameReferenceCommand class for Perspective Simulator CLI.
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
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * RenameReferenceCommand Class
 */
class RenameReferenceCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:rename-reference';

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
        $this->setDescription('Deletes a refernece between stores.');
        $this->setHelp('Deletes a refernece between stores.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'The type of store, eg, data or user.',
            null
        );

        $this->addArgument('storeName', InputArgument::REQUIRED, 'The name of the target store.');
        $this->addArgument('referenceName', InputArgument::REQUIRED, 'The name of the reference.');
        $this->addArgument('newReferenceName', InputArgument::REQUIRED, 'The new name of the reference.');

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
        $storeType = $input->getOption('type');
        if (empty($input->getOption('type')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which store type you are wanting to create.',
                ['data', 'user'],
                0
            );

            $storeType = $helper->ask($input, $output, $question);
            $input->setOption('type', $storeType);
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
     * Validates the name of the reference.
     *
     * @param string $name Name of the data store.
     *
     * @return string
     * @throws \Exception When name is invalid.
     */
    private function validateReferenceName(string $name)
    {
        if ($name === null) {
            throw new \Exception('Reference name is required.');
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            throw new \Exception('Invalid reference name provided');
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        $reference  = $this->storeDir.$this->args['targetCode'].'/'.$this->args['referneceName'].'.json';
        if (file_exists($reference) === true) {
            throw new \Exception('Reference name is already in use');
        }

        return $name;

    }//end validateReferenceName()


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
        $type             = $input->getOption('type');
        $targetCode       = $input->getArgument('storeName');
        $referenceName    = $input->getArgument('referenceName');
        $newReferenceName = $input->getArgument('referenceName');

        try {
            $oldRef = $this->storeDir.$targetCode.'/'.$referneceName.'.json';
            $newRef = $this->storeDir.$targetCode.'/'.$newReferenceName.'.json';
            if (file_exists($oldRef) === false) {
                throw new \Exception(
                    sprintf(
                        '%s doesn\'t exist.',
                        $referneceName
                    )
                );
            }

            $this->validateReferenceName($newReferenceName);

            Libs\Git::move($oldRef, $newRef);

            $this->logChange(
                'rename',
                lcfirst($this->type).'Reference',
                [
                    'from' => $referneceName,
                    'to'   => $newReferenceName,
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
