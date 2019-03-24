<?php
/**
 * PublishingStatusCommand for Perspective Simulator CLI.
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
 * PublishingStatusCommand Class
 */
class PublishingStatusCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:publishing:status';

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
        $this->setDescription('Gets the publishing status of a task.');
        $this->setHelp('Gets the publishing status of a task.');
        $this->addArgument('publishingJobID', InputArgument::REQUIRED, 'ID Returned from Gateway for the publishing job.');

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
        $this->style->title('Publishing status');
        $response = $this->sendAPIRequest(
            'get',
            '/publishing/progress/'.$publishingJobId,
            []
        );

        $response['result'] = json_decode($response['result'], true);

        $this->style->table(
            ['Job ID', 'Status', 'Attempts'],
            [
                [
                    $response['result']['publishingJobId'],
                    $response['result']['status'],
                    $response['result']['attempts'],
                ]
            ]
        );

    }//end execute()


}//end class
