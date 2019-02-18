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
        $this->addArgument('namespace', InputArgument::REQUIRED, 'The namespace of the project');

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
        $helper    = $this->getHelper('question');
        $namespace = ($input->getArgument('namespace') ?? null);
        if (empty($input->getArgument('namespace')) === true) {
            $question  = new \Symfony\Component\Console\Question\Question('Please enter a project namespace: ');
            $namespace = $helper->ask($input, $output, $question);
            $input->setArgument('namespace', $namespace);
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
        $namespace = $input->getArgument('namespace');

        $this->style->title('Creating new project');
        $response = $this->sendAPIRequest(
            'post',
            '/project',
            [
                'package'   => strtolower(str_replace('\\', '/', $namespace)),
                'namespace' => $namespace,
            ]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->success(sprintf('Project successfully created: %s', $response['result']['projectid']));
        } else {
            $this->style->error($response['result']);
        }

        $projectCommand = $this->getApplication()->find('project:add');
        $projectArgs    = [
            'command'   => 'project:add',
            'namespace' => $namespace,
        ];

        $projectInput = new \Symfony\Component\Console\Input\ArrayInput($projectArgs);
        $returnCode   = $projectCommand->run($projectInput, $output);

    }//end execute()


}//end class
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
        $this->addArgument('namespace', InputArgument::REQUIRED, 'The namespace of the project');

    }//end configure()


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
        $namespace = $input->getArgument('namespace');

        $this->style->title('Adding new project in gateway');
        $response = $this->sendAPIRequest(
            'post',
            '/project',
            [
                'package'   => strtolower(str_replace('\\', '/', $namespace)),
                'namespace' => $namespace,
            ]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->success(sprintf('Project successfully created: %s', $response['result']['projectid']));
        } else {
            $this->style->error($response['result']);
        }

        $projectCommand = $this->getApplication()->find('project:add');
        $projectArgs    = [
            'command'   => 'project:add',
            'namespace' => $namespace,
        ];

        $projectInput = new \Symfony\Component\Console\Input\ArrayInput($projectArgs);
        $returnCode   = $projectCommand->run($projectInput, $output);

    }//end execute()


}//end class
