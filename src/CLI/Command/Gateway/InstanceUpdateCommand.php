<?php
/**
 * InstanceUpdateCommand for Perspective Simulator CLI.
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
 * InstanceUpdateCommand Class
 */
class InstanceUpdateCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:update';


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
        $this->setDescription('Updates the instance.');
        $this->setHelp('Updates the instance.');
        $this->addArgument('instanceid', InputArgument::REQUIRED);

        $this->addOption(
            'projectVersion',
            'pv',
            InputOption::VALUE_REQUIRED,
            'Sets the project version of the instance.',
            null
        );

        $this->addOption(
            'activate',
            null,
            InputOption::VALUE_NONE,
            'Activates the instance.'
        );

        $this->addOption(
            'deactivate',
            null,
            InputOption::VALUE_NONE,
            'Deactivates the instance.'
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

        $helper     = $this->getHelper('question');
        $instanceid = ($input->getArgument('instanceid') ?? null);
        if (empty($input->getArgument('instanceid')) === true) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter the instanceid: ');
            $instanceid = $helper->ask($input, $output, $question);
            $input->setArgument('instanceid', $instanceid);
        }

        $projectVersion = $input->getOption('projectVersion');
        $activate       = $input->getOption('activate');
        $deactivate     = $input->getOption('deactivate');
        if ($projectVersion === null && $activate === false && $deactivate === false) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select the action to perform on the instance (default: <comment>0</comment>)',
                [
                    'Set the project version',
                    'Activate the instance',
                    'Deactivate the instance',
                ],
                0
            );

            $answer = $helper->ask($input, $output, $question);
            switch ($answer) {
                case 'Set the project version':
                    $question       = new \Symfony\Component\Console\Question\Question('Please enter the project version: ');
                    $projectVersion = $helper->ask($input, $output, $question);
                    $input->setOption('projectVersion', $projectVersion);
                break;

                case 'Activate the instance':
                    $input->setOption('activate', true);
                break;

                case 'Deactivate the instance':
                    $input->setOption('deactivate', true);
                break;
            }//end switch
        } else {
            $errorMsg = 'Please select only one option';
            if (($projectVersion !== null && ($activate === true || $deactivate === true))
                || ($activate === true && ($projectVersion !== null || $deactivate === true))
                || ($deactivate === true && ($projectVersion !== null && $activate === true))
            ) {
                $this->style->error($errorMsg);
                exit(1);
            }
        }//end if

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
        $instanceid     = $input->getArgument('instanceid');
        $project        = $input->getOption('project');
        $projectVersion = $input->getOption('projectVersion');
        $activate       = $input->getOption('activate');
        $deactivate     = $input->getOption('deactivate');
        if ($projectVersion !== null) {
            $response = $this->sendAPIRequest(
                'post',
                '/instance/'.$project.'/'.$instanceid.'/version/'.$projectVersion
            );
        } else if ($activate === true) {
            $response = $this->sendAPIRequest(
                'post',
                '/instance/'.$project.'/'.$instanceid.'/status/activate'
            );
        } else if ($deactivate === true) {
            $response = $this->sendAPIRequest(
                'post',
                '/instance/'.$project.'/'.$instanceid.'/status/deactivate'
            );
        }

    }//end execute()


}//end class
