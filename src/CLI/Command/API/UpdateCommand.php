<?php
/**
 * UpdateCommand class for Perspective Simulator CLI.
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
 * UpdateCommand Class
 */
class UpdateCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'api:update';

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
        $this->setDescription('Updates a projects API.');
        $this->setHelp('Updates a projects API.');
        $this->addArgument('path', InputArgument::OPTIONAL, 'Path to a new specification file.');

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
        $this->style->title('Update API');

        try {
            if ($input->getArgument('path') !== null) {
                $path = $input->getArgument('path');
                if (Libs\FileSystem::getExtension($path) !== 'yaml') {
                    throw new \Exception('Only yaml API specification files are supported.');
                }

                copy($path, $this->storeDir.'api.yaml');
            }

            \PerspectiveSimulator\API::installAPI($GLOBALS['project']);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class