<?php
/**
 * AddCommand class for Perspective Simulator CLI.
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

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'customtype:add';

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
        $this->addArgument('type', InputArgument::REQUIRED, 'Type of Custom type to create.');
        $this->addArgument('code', InputArgument::REQUIRED, 'Custom Type Code for Custom type being created.');
        $this->addArgument('parent', InputArgument::OPTIONAL, 'Optional parent of the Custom Type.');

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

        $customType = $input->getArgument('type');
        if (empty($input->getArgument('type')) === true) {
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
            $this->readableType = 'Custom Data Type';
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
            $type   = $input->getArgument('type');
            $code   = $input->getArgument('code');
            $parent = ($input->getArgument('parent') ?? 'DataRecord');

            $this->validatedCustomTypeCode($code);
            if (is_dir($this->storeDir) === false) {
                Libs\FileSystem::mkdir($this->storeDir, true);
            }

            // Check parent exits.
            if ($parent !== null && $parent !== $this->extends && file_exists($this->storeDir.$parent.'.json') === false) {
                $eMsg = sprintf('%s\'s parent doesn\'t exist.', $this->readableType);
                throw new \Exception($eMsg);
            }

            // PHP file.
            $defaultContent = Libs\Util::getDefaultPHPClass();
            $phpClass       = str_replace(
                'CLASS_NAME',
                $code,
                str_replace(
                    'CLASS_EXTENDS',
                    'extends '.$parent,
                    str_replace(
                        'NAMESPACE',
                        $this->namespace,
                        $defaultContent
                    )
                )
            );

            $phpFile = $this->storeDir.$code.'.php';
            file_put_contents($phpFile, $phpClass);

            // JSON file.
            $jsonData = [
                'name' => ucfirst($code),
                'icon' => [
                    'colour' => 'red',
                    'type'   => 'template',
                ],
            ];
            $jsonFile = $this->storeDir.$code.'.json';
            file_put_contents($jsonFile, Libs\Util::jsonEncode($jsonData));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
