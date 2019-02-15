<?php
/**
 * URLDeleteCommand for Perspective Simulator CLI.
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
 * URLDeleteCommand Class
 */
class URLDeleteCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'gateway:url:delete';

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
        $this->setDescription('Deletes a URL based on URL ID.');
        $this->setHelp('Deletes a URL based on URL ID.');
        $this->addArgument('urlids', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The URL IDs in the format seperated by spaces.');

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
        $this->style->title('Deleting URLs');

        $URLIds = $input->getArgument('urlids');
        foreach ($URLIds as $URLId) {
            $response = $this->sendAPIRequest(
                'delete',
                '/url/'.$input->getOption('project').'/url/'.$URLId,
                []
            );

            if ($response['curlInfo']['http_code'] === 200) {
                $this->style->success(sprintf('URL successfully deleted (%s)', $URLId));
            } else {
                $this->style->error(sprintf('Failed to delete URL (%1$s): %2$s', $URLId, $response['result']));
            }
        }

    }//end execute()


}//end class
