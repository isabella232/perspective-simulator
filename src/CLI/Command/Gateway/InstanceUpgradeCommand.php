<?php
/**
 * InstanceUpgradeCommand for Perspective Simulator CLI.
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
 * InstanceUpgradeCommand Class
 */
class InstanceUpgradeCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:upgrade';


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
        $this->setDescription('Upgrades instances to the latest version.');
        $this->setHelp('Upgrades instances to the latest version, they can run on.');

        $this->addOption(
            'testInstances',
            't',
            InputOption::VALUE_NONE,
            'Flag to upgrade the test instances only.'
        );

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

        // First list all the instances that we are going to be affected.
        $response = $this->sendAPIRequest('get', '/instance/'.$input->getOption('project'), []);
        if ($response['curlInfo']['http_code'] !== 200) {
            $this->style->error($response['result']);
            exit(1);
        }

        $testInstances      = ($input->getOption('testInstances') ?? false);
        $response['result'] = json_decode($response['result'], true);
        $response['result'] = array_filter(
            $response['result'],
            function ($a) use ($testInstances) {
                if ($testInstances === false) {
                    return true;
                } else {
                    if ($a['upgrade'] === 'test') {
                        return true;
                    }
                }

                return false;
            }
        );

        $this->instances = [];
        foreach ($response['result'] as $key => $instance) {
            $this->instances[$instance['instanceid']] = $instance['instanceid'].' - '.$instance['name'].' ('.$instance['upgrade'].')';
        }

        $this->style->listing($this->instances);

        $response = $this->sendAPIRequest('post', '/instance/'.$input->getOption('project').'/latest/version', ['testInstances' => $testInstances]);
        if ($response['curlInfo']['http_code'] !== 200) {
            $this->style->error($response['result']);
            exit(1);
        }

        $latestVersion = json_decode($response['result'], true);
        $helper        = $this->getHelper('question');
        $confirm       = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'The above instances will be upgraded to the latest available version (<comment>'.($latestVersion['simulator_version'] ?? '').'</comment>). Do you wish to continue? (y/N)',
            false
        );

        $continue = $helper->ask($input, $output, $confirm);
        if ($continue === false) {
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
        $testInstances = $input->getOption('testInstances');
        $this->style->title(sprintf('Upgrading instances for project: %s', $input->getOption('project')));

        $response = $this->sendAPIRequest(
            'post',
            '/instance/'.$input->getOption('project').'/upgrade',
            [
                'instances' => Libs\Util::jsonEncode(array_keys($this->instances)),
                'testOnly'  => $testInstances,
            ]
        );

        if ($response['curlInfo']['http_code'] !== 200) {
            $this->style->error($response['result']);
            exit(1);
        }

        $response['result'] = json_decode($response['result'], true);
        $this->style->text('<comment>'.sprintf('Publishing Job ID for this task is: %s', $response['result']['publishingJobId']).'</comment>');
        $this->style->success('The following instances ('.implode(', ', $response['result']['upgraded']).') were upgraded to version '.$response['result']['version']);

    }//end execute()


}//end class
