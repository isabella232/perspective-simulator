<?php
/**
 * ProjectAddURLCommand for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Gateway;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * ProjectAddURLCommand Class
 */
class ProjectAddURLCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:project:addURL';

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
        $this->setDescription('Sets the URLs on a project.');
        $this->setHelp('Sets the URLs on a project.');

        $this->addArgument('urls', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The URLs in the format type:url seperated by spaces.');

    }//end configure()


    /**
     *
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
        $this->style->title('Setting project URLs');

        // Prepare the settings array the input is in the format key:value key:value.
        $URLs = $input->getArgument('urls');
        foreach ($URLs as $URL) {
            list($key, $value) = explode(':', $URL);
            $response = $this->sendAPIRequest(
                'post',
                '/url/'.$input->getOption('project').'/project',
                [
                    'type' => $key,
                    'url'  => $value,
                ]
            );

            if ($response['curlInfo']['http_code'] === 200) {
                $this->style->success(sprintf('Project URL set: %1$s - %2$s', $key, $value));
            } else {
                $this->style->error($response['result']);
            }
        }

    }//end execute()


}//end class
