<?php
/**
 * ProjectDeployStatusCommand for Perspective Simulator CLI.
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
 * ProjectDeployStatusCommand Class
 */
class ProjectDeployStatusCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:project:deployStatus';

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
        $this->setDescription('Gets the deployment status of a project.');
        $this->setHelp('Gets the deployment status of a project.');
        $this->addArgument('deploymentid', InputArgument::REQUIRED, 'ID Returned from gateway:project:deploy command');

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
        $this->style->title('Deployment status');
        $response = $this->sendAPIRequest(
            'get',
            '/deployment/progress/'.$input->getArgument('deploymentid'),
            []
        );

        $response['result'] = json_decode($response['result'], true);
        $complete           = false;
        $error              = '';
        if ($response['result']['complete'] === $response['result']['total']) {
            $complete = true;
        }

        if (empty($response['result']['errorCode']) === false) {
            $error = implode('-', $response['result']['errorCode']);
        }

        $this->style->table(
            ['Status', 'Complete', 'Error'],
            [
                [
                    $response['result']['status'],
                    var_export($complete, 1),
                    $error,
                ]
            ]
        );

    }//end execute()


}//end class
