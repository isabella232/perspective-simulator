<?php
/**
 * InstancePropertyCommand for Perspective Simulator CLI.
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
 * InstancePropertyCommand Class
 */
class InstancePropertyCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:property';

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
        $this->setDescription('Sets a property value on an instance.');
        $this->setHelp('Sets a property value on an instance.');
        $this->addOption(
            'propertyCode',
            'pc',
            InputOption::VALUE_REQUIRED,
            'The property code for the property we are setting the value for.',
            null
        );

        $this->addOption(
            'instanceid',
            'i',
            InputOption::VALUE_REQUIRED,
            'The instanceid of the instance we are setting the property value for.'
        );

        $this->addArgument('value', InputArgument::REQUIRED, 'The value that will be set.');

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
        $value  = ($input->getArgument('value') ?? null);
        if (empty($input->getArgument('value')) === true) {
            $question = new \Symfony\Component\Console\Question\Question('Please enter a value for the property: ');
            $value    = $helper->ask($input, $output, $question);
            $input->setArgument('value', $value);
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
        $propertyCode = $input->getOption('propertyCode');
        $this->style->title(sprintf('Setting property value for %s', $propertyCode));
        $response = $this->sendAPIRequest(
            'post',
            '/instance/'.$input->getOption('project').'/'.$input->getOption('instanceid').'/property',
            [
                'code'  => $propertyCode,
                'value' => $input->getArgument('value'),
            ]
        );

        if ($response['curlInfo']['http_code'] !== 200) {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
