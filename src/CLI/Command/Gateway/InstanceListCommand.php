<?php
/**
 * InstanceListCommand for Perspective Simulator CLI.
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
 * InstanceListCommand Class
 */
class InstanceListCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:instance:list';

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
        $this->setDescription('Lists all the instances for a project.');
        $this->setHelp('Lists all the instances for a project.');
        $this->addArgument('filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Optional list of instance ids to filter the list by seperated by spaces, eg. 1.1 10.1');

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
        $this->style->title(sprintf('Instances for project: %s', $input->getOption('project')));
        $response = $this->sendAPIRequest('get', '/instance/'.$input->getOption('project'), []);

        if ($response['curlInfo']['http_code'] === 200) {
            $response['result'] = json_decode($response['result'], true);

            $filter = ($input->getArgument('filter') ?? []);
            if (empty($filter) === false) {
                // Filter to instaces down.
                $response['result'] = array_filter(
                    $response['result'],
                    function ($a) use ($filter) {
                        if (in_array($a['instanceid'], $filter) === true) {
                            return true;
                        }

                        return false;
                    }
                );
            }

            if (empty($response['result']) === false){
                $this->style->table(array_keys($response['result'][0]), $response['result']);
            } else {
                $this->style->note('No instances found for project.');
            }
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
