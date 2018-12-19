<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
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

use \PerspectiveSimulator\Libs;

/**
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'property:delete';

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
        $this->setDescription('Deletes a Property.');
        $this->setHelp('Deletes a Property.');
        $this->addArgument('propType', InputArgument::REQUIRED, 'Type of property eg, DataRecord, Project or User.');
        $this->addArgument('code', InputArgument::REQUIRED, 'Property code for the property being created.');

    }//end configure()


    /**
     * Make sure that the system name is set.
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inProject($input, $output);

        $helper   = $this->getHelper('question');
        $propType = $input->getArgument('propType');
        if (empty($input->getArgument('propType')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which custom type you are wanting to create.',
                ['DataRecord', 'Project', 'User',],
                0
            );

            $propType = $helper->ask($input, $output, $question);
            $input->setArgument('propType', $propType);
            $output->writeln('You have just selected: '.$propType);
        }

        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'This will delete '.$propType.' type property "'.$input->getArgument('code').'"',
            false
        );
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
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
            $code = $input->getArgument('code');
            $propertyFile = $this->storeDir.$code.'.json';
            if (file_exists($propertyFile) === false) {
                throw new CLIException(
                    sprintf(
                        '%s property doesn\'t exist.',
                        $code
                    )
                );
            }

            Libs\FileSystem::delete($propertyFile);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class