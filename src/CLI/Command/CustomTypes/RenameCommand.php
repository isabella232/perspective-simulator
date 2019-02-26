<?php
/**
 * RenameCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\CustomTypes;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * RenameCommand Class
 */
class RenameCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'customtype:rename';

    /**
     * The direcrtory where the export stores the data.
     *
     * @var string
     */
    private $storeDir = null;

    private $type = null;

    private $readableType = null;

    private $namespace = null;

    private $extends = null;


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Adds a new Custom Type.');
        $this->setHelp('Adds a new Custom Type.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'Type of Custom type to create.',
            null
        );

        $this->addArgument('code', InputArgument::REQUIRED, 'Custom Type Code for Custom type being renamed.');
        $this->addArgument('newCode', InputArgument::REQUIRED, 'The new Custom Type Code.');

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

        $helper = $this->getHelper('question');

        $customType = $input->getOption('type');
        if (empty($input->getOption('type')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which custom type you are wanting to create.',
                ['DataType'],
                0
            );

            $customType = $helper->ask($input, $output, $question);
            $input->setArgument('type', $customType);
            $output->writeln('You have just selected: '.$customType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($customType) === 'datatype') {
            $this->storeDir     = $projectDir.'/CustomTypes/Data/';
            $this->type         = 'customdatatype';
            $this->readableType = 'Custom Data Record Type';
            $this->namespace    = $GLOBALS['projectNamespace'].'\\CustomTypes\\Data';
            $this->extends      = 'DataRecord';
        }

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end interact()


    /**
     * Validates the custom type code.
     *
     * @param string $code The custom type code.
     *
     * @return string
     * @throws CLIException When code is invalid.
     */
    private function validatedCustomTypeCode(string $code)
    {
        if ($code === null) {
            $eMsg = sprintf('%s code is required.', $this->readableType);
            throw new \Exception($eMsg);
        }

        $bannedTypeNames = [
            'data',
            'page',
            'user',
            'group',
        ];

        foreach ($bannedTypeNames as $banned) {
            if ($banned === strtolower($code)) {
                throw new \Exception('Invalid custom type name');
            }
        }

        $valid = Libs\Util::isPHPClassString($code);

        if ($valid === false) {
            $eMsg = sprintf('Invalid %s code provided', $this->readableType);
            throw new \Exception($eMsg);
        }

        $customType = $this->storeDir.$code.'.json';
        if (file_exists($customType) === true) {
            $eMsg = sprintf('Duplicate %s code provided', $this->readableType);
            throw new \Exception($eMsg);
        }

        return $code;

    }//end validatedCustomTypeCode()


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
            $type    = $input->getOption('type');
            $code    = $input->getArgument('code');
            $newCode = $input->getArgument('newCode');

            $this->validatedCustomTypeCode($newCode);

            // PHP file.
            $phpFile      = $this->storeDir.$code.'.php';
            $classContent = file_get_contents($phpFile);
            $phpClass     = str_replace(
                'class '.$code,
                'class '.$newCode,
                $classContent
            );
            file_put_contents($phpFile, $phpClass);

            Libs\Git::move($phpFile, $this->storeDir.$newCode.'.php');

            $this->logChange(
                'rename',
                str_replace(' ', '', $this->readableType),
                [
                    'from' => $code,
                    'to'   => $newCode,
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
