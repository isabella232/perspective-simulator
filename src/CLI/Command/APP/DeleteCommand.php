<?php
/**
 * DeleteCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\APP;

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
        $this->setDescription('Deletes an App class or directory.');
        $this->setHelp('Deletes an App class or directory.');
        $this->addArgument('type', InputArgument::REQUIRED, 'The type we are adding or deleting, eg: class or direcrtory.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The path to the file or directory (this is realative to the APP folder).');

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
            'This will delete App '.$input->getArgument('type').' "'.$input->getArgument('name').'"',
            false
        );
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
            $name = $input->getArgument('name');
            $type = $input->getArgument('type');
            if ($type === 'directory') {
                $path = $this->storeDir.$name;
                if (is_dir($path) === false) {
                    $eMsg = sprintf('The directory "%s" doesn\'t exist.', $path);
                    throw new \Exception($eMsg);
                }
            } else {
                // Remove .php incase it was provided we will readd to ensure its there.
                $path = str_replace('.php', '', $this->storeDir.$name);
                $path = $path.'.php';

                if (file_exists($path) === false) {
                    $eMsg = sprintf('App class "%s" doesn\'t exist.', $path);
                    throw new \Exception($eMsg);
                }
            }//end if

            Libs\Git::delete($path);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class