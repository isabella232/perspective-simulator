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
 * AddCommand Class
 */
class RenameCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'queue:rename';

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
        $this->setDescription('Renames a Queue.');
        $this->setHelp('Rename a Queue.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The current name of the Queue');
        $this->addArgument('newName', InputArgument::REQUIRED, 'The new name of the new Queue');

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

        $projectDir     = Libs\FileSystem::getProjectDir();
        $this->storeDir = $projectDir.'/Queues/';
        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end interact()


    /**
     * Validates a queue name.
     *
     * @param string $name The name to validate.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateQueueName(string $name)
    {
        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            throw new \Exception('Queue name invalid.');
        }

        $queueFile = $this->storeDir.$name.'.php';
        if (file_exists($queueFile) === true) {
            throw new \Exception('Duplicate queue name.');
        }

        return $name;

    }//end validateQueueName()

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
            $oldName = $input->getArgument('name');
            $newName = $input->getArgument('newName');

            if (file_exists($this->storeDir.$oldName.'.php') === false) {
                throw new \Exception('Queue doesn\'t exist.');
            }

            $this->validateQueueName($newName);
            Libs\FileSystem::move(
                $this->storeDir.$oldName.'.php',
                $this->storeDir.$newName.'.php'
            );
            $this->rebake();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
