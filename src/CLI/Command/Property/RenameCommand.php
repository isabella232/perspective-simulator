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
class RenameCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'property:rename';

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
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Renames a Property.');
        $this->setHelp('Renames a Property.');
        $this->addOption(
            'proptype',
            't',
            InputOption::VALUE_REQUIRED,
            'Type of property eg, data, project or user.',
            null
        );

        $this->addArgument('code', InputArgument::REQUIRED, 'Property code with type for the property being rename.');
        $this->addArgument('newCode', InputArgument::REQUIRED, 'New property code with type for the property being renamed.');

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
                ['Data', 'Project', 'User',],
                0
            );

            $propType = $helper->ask($input, $output, $question);
            $input->setOption('proptype', $propType);
            $output->writeln('You have just selected: '.$propType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($propType) === 'data') {
            $this->storeDir     = $projectDir.'/Properties/Data/';
            $this->type         = 'DataRecord';
            $this->readableType = 'Data';
        } else if (strtolower($propType) === 'project') {
            $this->storeDir     = $projectDir.'/Properties/Project/';
            $this->type         = 'Project';
            $this->readableType = 'Project';
        } else if (strtolower($propType) === 'user') {
            $this->storeDir     = $projectDir.'/Properties/User/';
            $this->type         = 'User';
            $this->readableType = 'User';
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

        $valid = Libs\Util::isValidStringid($code, true);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s property code provided', $this->readableType);
            throw new \Exception($eMsg);
        }

        $properties = $this->simulatorHandler->getProperties($this->type);
        if (array_key_exists($code, $properties) === true) {
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
            $namespace = $input->getOption('project');
            $propType  = $input->getOption('proptype');
            $code      = $input->getArgument('code');
            $newCode   = $input->getArgument('newCode');

            list($oldPropid, $oldPropType) = \PerspectiveSimulator\Bootstrap::getPropertyInfo($namespace.'/'.$code);
            list($propid, $propType)       = \PerspectiveSimulator\Bootstrap::getPropertyInfo($namespace.'/'.$newCode);

            if ($oldPropType !== $propType) {
                throw new \Exception('Property types must match');
            }

            $this->validatedPropertyCode(basename($propid));

            $propType = 'data';
            if ($this->type === 'User') {
                $propType = 'user';
            } else if ($this->type === 'Project') {
                $propType = 'project';
            }

            $properties = $this->simulatorHandler->getProperties($propType);
            if (isset($properties[$oldPropid]) === true) {
                // Sim has saved data and the old property has values set.
                $properties[$propid] = $properties[$oldPropid];
                unset($properties[$oldPropid]);
            }

            $this->simulatorHandler->setProperties($properties, $propType);

            $this->logChange(
                'rename',
                $this->type.'Property',
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
