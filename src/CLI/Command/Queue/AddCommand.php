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
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'queue:add';

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
        $this->setDescription('Adds a new Queue.');
        $this->setHelp('Adds a new Queue.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the new Queue');

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
            $name = $input->getArgument('name');

            $this->validateQueueName($name);
            $defaultQueue = '
/**
 * Queue function for QUEUE_NAME.
 *
 * @param object $job The job object passed.
 */

';

            $queueCode = str_replace(
                'QUEUE_NAME',
                $name,
                $defaultQueue
            );

            $queueFile = $this->storeDir.$name.'.php';
            file_put_contents($queueFile, $queueCode);

            $this->rebake();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
