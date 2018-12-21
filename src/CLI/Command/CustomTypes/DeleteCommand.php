<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
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
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'customtype:delete';

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
        $this->setDescription('Deletes a Custom Type from the project.');
        $this->setHelp('Deletes a Custom Type from the project.');
        $this->addArgument('type', InputArgument::REQUIRED, 'Type of Custom type to be deleted.');
        $this->addArgument('code', InputArgument::REQUIRED, 'Custom Type Code for Custom type being deleted.');

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

        $helper     = $this->getHelper('question');
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

        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'This will delete Custom type "'.$input->getArgument('cpPath').'"',
            false
        );
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if ($customType === 'DataType') {
            $this->storeDir     = $projectDir.'/CustomTypes/Data/';
            $this->type         = 'customdatatype';
            $this->readableType = 'Custom Data Type';
            $this->namespace    = $GLOBALS['project'].'\\CustomTypes\\Data';
            $this->extends      = 'DataRecord';
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
            $customTypeFile = $this->storeDir.$code.'.json';
            if (file_exists($customTypeFile) === false) {
                throw new \Exception(
                    sprintf(
                        '%1$s "%2$s" doesn\'t exist.',
                        $this->readableType,
                        $code
                    )
                );
            }

            Libs\Git::delete($customTypeFile);
            Libs\Git::delete($this->storeDir.$code.'.php');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class