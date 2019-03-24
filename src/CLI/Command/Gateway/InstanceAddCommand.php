<?php
/**
 * InstanceAddCommand for Perspective Simulator CLI.
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
 * InstanceAddCommand Class
 */
class InstanceAddCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:add';

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
        $this->setDescription('Registers the instance to a Gateway.');
        $this->setHelp('Registers the instance to a Gateway.');
        $this->addOption(
            'upgrade',
            'u',
            InputOption::VALUE_OPTIONAL,
            'Flag for which upgrades this instance should receive.',
            'stable'
        );
        $this->addArgument('name', InputArgument::REQUIRED);

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

        $helper = $this->getHelper('question');
        $name   = ($input->getArgument('name') ?? null);
        if (empty($input->getArgument('name')) === true) {
            $question = new \Symfony\Component\Console\Question\Question('Please enter an Instance name: ');
            $name     = $helper->ask($input, $output, $question);
            $input->setArgument('name', $name);
        }

        $upgradeOptions = ['test', 'stable'];
        $upgrade        = $input->getOption('upgrade');
        if (in_array($upgrade, $upgradeOptions) === false) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select one of the following upgrade rules:',
                $upgradeOptions,
                0
            );

            $upgrade = $helper->ask($input, $output, $question);
            $input->setOption('upgrade', $upgrade);
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
        $this->style->title('Creating new instance');
        $response = $this->sendAPIRequest(
            'post',
            '/instance/'.$input->getOption('project'),
            [
                'name'    => $input->getArgument('name'),
                'upgrade' => $input->getOption('upgrade'),
            ]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->text('<comment>'.sprintf('Publishing Job ID for this task is: %s', $response['result']['publishingJobId']).'</comment>');
            $this->style->success(sprintf('Instance successfully created: %s %s', $response['result']['instanceid'], $response['result']['appKey']));
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
