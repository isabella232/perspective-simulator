<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\CDN;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'cdn:delete';

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
        $this->setDescription('Deletes a file/directory from the CDN.');
        $this->setHelp('Deletes a file/directory from the CDN.');
        $this->addArgument('cpPath', InputArgument::REQUIRED, 'The path of the file/directory to remove from the CDN.');

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

        $helper  = $this->getHelper('question');
        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'This will delete CDN file/directory "'.$input->getArgument('cpPath').'"',
            false
        );
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
        }

        $projectDir     = Libs\FileSystem::getProjectDir();
        $this->storeDir = $projectDir.'/CDN/';
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
            $cpPath = $input->getArgument('cpPath');
            if (is_dir($this->storeDir.$cpPath) === false && file_exists($this->storeDir.$cpPath) === false) {
                throw new \Exception('Invalid CDN file/directory.');
            }

            Libs\Git::delete($this->storeDir.$cpPath);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class