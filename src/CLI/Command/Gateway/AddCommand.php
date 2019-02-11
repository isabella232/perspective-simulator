<?php
/**
 * Project class for Perspective Simulator CLI.
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
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'gateway:add';

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
        $this->setDescription('Registers the simulator to a Gateway.');
        $this->setHelp('Registers the simulator to a Gateway.');
        $this->addOption(
            'key',
            'k',
            InputOption::VALUE_REQUIRED,
            'The key for the gateway.',
            null
        );
        $this->addArgument('gatewayURL', InputArgument::OPTIONAL, 'Optional URL of the gateway network.');

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
        $this->key = ($input->getOption('key') ?? null);
        if ($this->key === null) {
            $this->style->error('Gatewway key must be provided.');
            exit(1);
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
        $gateway = new \PerspectiveSimulator\Gateway();
        $gateway->setGatewayKey($this->key);

        $gatewayURL = ($input->getArgument('gatewayURL') ?? null);
        if ($gatewayURL !== null) {
            $gateway->setGatewayURL($gatewayURL);
        }

        $this->style->success('Gateway successfully registered.');
        $this->style->note('It is suggested that you DO NOT commit .apiKey to your repo.');

    }//end execute()


}//end class
