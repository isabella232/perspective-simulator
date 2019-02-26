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
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddReferenceCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:add-reference';

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
        $this->setDescription('Adds a new refernece between stores.');
        $this->setHelp('Adds a new refernece between stores.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'The type of store, eg, data or user.',
            null
        );
        $this->addOption(
            'sourceType',
            's',
            InputOption::VALUE_OPTIONAL,
            'The type of store, eg, data or user.',
            null
        );

        $this->addArgument('storeName', InputArgument::REQUIRED, 'The name of the target store.');
        $this->addArgument('referenceName', InputArgument::REQUIRED, 'The name of the reference.');
        $this->addArgument('sourceStore', InputArgument::REQUIRED, 'The type of store, eg, data or user.');
        $this->addArgument('cardinality', InputArgument::OPTIONAL, 'The cardinality of the reference, eg. 1:1, 1:M or M:M');

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

        $sourceType = $input->getOption('sourceType');
        if ($sourceType === null){
            $input->setOption('sourceType', $storeType);
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

        $this->targetCode = $input->getArgument('storeName');

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
        $reference  = $this->storeDir.$this->targetCode.'/'.$name.'.json';
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
        $type          = $input->getOption('type');
        $sourceType    = $input->getOption('sourceType');
        $targetCode    = $input->getArgument('storeName');
        $referenceName = $input->getArgument('referenceName');
        $sourceStore   = $input->getArgument('sourceStore');
        $cardinality   = ($input->getArgument('cardinality') ?? 'M:M');

        if (is_dir($this->storeDir.$targetCode) === false) {
            throw new \Exception(sprintf('%s doesn\'t exist.', $this->readableType));
        }

        $projectDir     = Libs\FileSystem::getProjectDir();
        $sourceStoreDir = $projectDir.'/Stores/';
        if (strtolower($sourceType) === 'user') {
            $sourceStoreDir .= 'User/';
            $sourceType      = 'UserStore';
        } else if (strtolower($sourceType) === 'data') {
            $sourceStoreDir .= 'Data/';
            $sourceType      = 'DataStore';
        } else {
            $sourceStoreDir = $this->storeDir;
            $sourceType     = 'DataStore';
        }

        $targetType = 'DataStore';
        if (strtolower($type) === 'user') {
            $targetType = 'UserStore';
        }

        if (is_dir($sourceStoreDir.$sourceStore) === false) {
            throw new \Exception(sprintf('%s doesn\'t exist.', $sourceStore));
        }

        try {
            $this->validateReferenceName($referenceName);
            $referneceDetails = [
                'sourceType'  => $sourceType,
                'sourceCode'  => $sourceStore,
                'targetType'  => $targetType,
                'targetCode'  => $targetCode,
                'cardinality' => $cardinality,
            ];

            $path = $this->storeDir.$targetCode.'/'.$referenceName.'.json';
            file_put_contents($path, Libs\Util::jsonEncode($referneceDetails));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
