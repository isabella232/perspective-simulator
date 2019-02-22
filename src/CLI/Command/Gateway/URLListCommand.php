<?php
/**
 * URLListCommand for Perspective Simulator CLI.
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
 * URLListCommand Class
 */
class URLListCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:url:list';

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
        $this->setDescription('Lists all the URLs for a project and its instances.');
        $this->setHelp('Lists all the URLs for a project and its instances.');

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
        $this->style->title(sprintf('URLs for project: %s', $input->getOption('project')));
        $response = $this->sendAPIRequest('get', '/url/'.$input->getOption('project'), []);
        if ($response['curlInfo']['http_code'] === 200) {
            $response['result'] = json_decode($response['result'], true);

            $this->style->section('Project URLs');
            $projectURLs = $response['result']['project'];
            if (empty($projectURLs) === false) {
                $this->style->table(
                    array_keys($projectURLs[0]),
                    $projectURLs
                );
            } else {
                $this->style->note('No project URLs set.');
            }

            $this->style->section('Instance URLs');
            $instanceURLs = $response['result']['instance'];
            if (empty($instanceURLs) === false) {
                $this->style->table(
                    array_keys($instanceURLs[0]),
                    $instanceURLs
                );
            } else {
                $this->style->note('No instance URLs set.');
            }
        } else if ($response['curlInfo']['http_code'] === 403) {
            $this->style->error('Forbidden');
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
