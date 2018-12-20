<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\API;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * DeleteCommand Class
 */
class DeleteCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'api:delete';

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
        $this->setDescription('Deletes the API from the project.');
        $this->setHelp('Deletes an API specification file and its operations from the project.');

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
        $helper = $this->getHelper('question');
        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion('This will delete API', false);
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
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
            Libs\FileSystem::delete($this->storeDir.'api.yaml');
            Libs\FileSystem::delete($this->storeDir.'/Operations/');
            $simDir = Libs\FileSystem::getSimulatorDir();
            Libs\FileSystem::delete($simDir.$GLOBALS['projectPath'].'/API.php');
            Libs\FileSystem::delete($simDir.$GLOBALS['projectPath'].'/APIRouter.php');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
