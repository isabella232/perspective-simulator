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
        $this->addArgument('vendor', InputArgument::REQUIRED);
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
        $helper = $this->getHelper('question');
        $name   = ($input->getArgument('name') ?? null);
        if (empty($input->getArgument('name')) === true) {
            $question = new \Symfony\Component\Console\Question\Question('Please enter an Instance name: ');
            $name     = $helper->ask($input, $output, $question);
            $input->setArgument('name', $name);
        }

        $project = ($input->getOption('project') ?? null);
        if (empty($project) === true) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter the project: ');
            $instanceid = $helper->ask($input, $output, $question);
            $input->setOption('project', $project);
        }

        $question = sprintf('This will create a new instance "%s" in the project "%s": ', $name, $project);
        $confirm  = new \Symfony\Component\Console\Question\ConfirmationQuestion($question, false);
        if ($helper->ask($input, $output, $confirm) === false) {
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
        $this->style->title('Creating new project');
        $response = $this->sendAPIRequest(
            'post',
            '/project/'.$input->getArgument('vendor').'/'.$input->getArgument('name'),
            []
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->success(sprintf('Project successfully created: %s', $response['result']['projectid']));
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
