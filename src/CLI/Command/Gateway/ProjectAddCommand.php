<?php
/**
 * ProjectAddCommand for Perspective Simulator CLI.
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
 * ProjectAddCommand Class
 */
class ProjectAddCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:project:add';

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
        $this->setDescription('Creates a new project in Gateway.');
        $this->setHelp('Creates a new project in Gateway.');

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
        $this->style->title('Adding new project in gateway');
        $response = $this->sendAPIRequest(
            'post',
            '/project',
            ['package' => strtolower(str_replace('\\', '/', $input->getOption('project')))]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->success(sprintf('Project successfully created: %s', $response['result']['projectid']));
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
