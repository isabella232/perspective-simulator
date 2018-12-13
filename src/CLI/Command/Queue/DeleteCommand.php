<?php
/**
 * AddCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Queue;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'queue:delete';

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
        $this->setDescription('Deletes a Queue.');
        $this->setHelp('Deletes a Queue.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the Queue being deleted.');

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

        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'This will delete Queue "'.$input->getArgument('name').'"',
            false
        );
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
        }

        $projectDir     = Libs\FileSystem::getProjectDir();
        $this->storeDir = $projectDir.'/Queues/';
        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end interact()


    /**
     * Rebakes the queue functions.
     *
     * @return void
     */
    private function rebake()
    {
        \PerspectiveSimulator\Queue\Queue::installQueues($GLOBALS['project']);

    }//end rebake()


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
            $name = $input->getArgument('name');

            Libs\FileSystem::delete($this->storeDir.$name.'.php');
            $this->rebake();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
