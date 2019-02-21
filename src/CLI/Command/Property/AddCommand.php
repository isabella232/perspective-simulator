<?php
/**
 * AddCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Property;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'property:add';

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
    private $storeDir = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';

    /**
     * Array of initial settings for each property type.
     *
     * @var array
     */
    private $defaultSettings = [
        'unique'    => [],
        'boolean'   => [],
        'datetime'  => ['display' => 'dmy'],
        'html'      => [
            'toolbar'       => false,
            'blockTag'      => 'p',
            'customToolbar' => [
                'core'     => [],
                'format'   => [],
                'justify'  => [],
                'lists'    => [],
                'linking'  => [],
                'history'  => [],
                'styles'   => [],
                'aa'       => false,
                'image'    => false,
                'sv'       => false,
                'language' => false,
                'cm'       => false,
                'search'   => false,
                'table'    => false,
            ],
            'customStyles'  => [],
        ],
        'integer'   => [
            'suffix'    => '',
            'increment' => 1,
            'minimum'   => 0,
            'maximum'   => 100,
        ],
        'number'    => [
            'suffix'    => '',
            'increment' => 1,
            'minimum'   => 0,
            'maximum'   => 100,
        ],
        'pageid'    => ['multiple' => false],
        'recordset' => ['fields' => []],
        'selection' => [
            'multiple' => false,
            'options'  => [],
        ],
        'text'      => ['multiline' => false],
        'userid'    => ['multiple' => false],
        'image'     => [
            'maxFileSize'     => null,
            'dimensions'      => [],
            'decorative'      => false,
            'constraint'      => 0,
            'isWebAccessible' => false,
        ],
        'file'      => [
            'maxFileSize'       => null,
            'allowedExtensions' => [],
            'isWebAccessible'   => false,
        ],
    ];


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Adds a new Property.');
        $this->setHelp('Adds a new Property.');
        $this->addOption(
            'proptype',
            't',
            InputOption::VALUE_REQUIRED,
            'Type of property eg, DataRecord, Project or User.',
            null
        );
        $this->addArgument('code', InputArgument::REQUIRED, 'Property code for the property being created.');
        $this->addArgument('type', InputArgument::REQUIRED, 'Property Type, eg text, number.');

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

        $helper   = $this->getHelper('question');
        $propType = $input->getOption('proptype');
        if (empty($input->getOption('proptype')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which custom type you are wanting to create.',
                ['DataRecord', 'Project', 'User',],
                0
            );

            $propType = $helper->ask($input, $output, $question);
            $input->setOption('proptype', $propType);
            $output->writeln('You have just selected: '.$propType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($propType) === 'datarecord') {
            $this->storeDir     = $projectDir.'/Properties/Data/';
            $this->type         = 'datarecord';
            $this->readableType = 'Data Record';
        } else if (strtolower($propType) === 'project') {
            $this->storeDir     = $projectDir.'/Properties/Project/';
            $this->type         = 'project';
            $this->readableType = 'Project';
        } else if (strtolower($propType) === 'user') {
            $this->storeDir     = $projectDir.'/Properties/User/';
            $this->type         = 'user';
            $this->readableType = 'User';
        }

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end interact()


    /**
     * Validates the property code.
     *
     * @param string $code The property code.
     *
     * @return string
     * @throws CLIException When code is invalid.
     */
    private function validatedPropertyCode(string $code)
    {
        if ($code === null) {
            $eMsg = sprintf('%s property code is required.', $this->readableType);
            throw new \Exception($eMsg);
        }

        $valid = Libs\Util::isValidStringid($code);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s property code provided', $this->readableType);
            throw new \Exception($eMsg);
        }

        $property = $this->storeDir.$code.'.json';
        if (file_exists($property) === true) {
            throw new \Exception('Property Code is already in use');
        }

        return $code;

    }//end validatedPropertyCode()


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
            $propType = $input->getOption('proptype');
            $code     = $input->getArgument('code');
            $type     = $input->getArgument('type');

            $this->validatedPropertyCode($code);
            if (is_dir($this->storeDir) === false) {
                Libs\FileSystem::mkdir($this->storeDir, true);
            }

            $propertyFile      = $this->storeDir.$code.'.json';
            $propData          = $this->defaultSettings[$type];
            $propData['title'] = $code;
            file_put_contents(
                $propertyFile,
                Libs\Util::jsonEncode(
                    [
                        'type'     => $type,
                        'settings' => $propData,
                    ]
                )
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
