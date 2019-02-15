<?php
/**
 * InstanceAPISettingsCommand for Perspective Simulator CLI.
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
 * InstanceAPISettingsCommand Class
 */
class InstanceAPISettingsCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:apiSettings';

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
        $this->setDescription('Sets the API settings on an instance.');
        $this->setHelp('Sets the API settings on an instance.');

        $this->addOption(
            'instanceid',
            'i',
            InputOption::VALUE_REQUIRED,
            'The instanceid of the instance we are setting the property value for.'
        );

        $this->addArgument('settings', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The API settings seperate values with spaces (in format key:value).');

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
        $this->style->title('Setting the instance API settings');

        // Prepare the apiSettings array the input is in the format key:value key:value.
        $settings    = $input->getArgument('settings');
        $apiSettings = [];
        foreach ($settings as $setting) {
            list($key, $value) = explode(':', $setting);
            $apiSettings[$key] = $value;
        }

        $response = $this->sendAPIRequest(
            'post',
            '/instance/'.$input->getOption('project').'/'.$input->getOption('instanceid').'/apiSettings',
            ['apiSettings' => json_encode($apiSettings),]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $this->style->success(sprintf('Updated API settings on instance %s', $input->getOption('instanceid')));
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
