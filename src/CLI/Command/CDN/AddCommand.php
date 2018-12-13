<?php
/**
 * AddCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\CDN;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'cdn:add';

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
        $this->setDescription('Adds a new file/directory to the CDN.');
        $this->setHelp('Adds a new file/directory to the CDN.');
        $this->addArgument('cpPath', InputArgument::REQUIRED, 'The path of the file to copy or the CDN path of a directory to create.');
        $this->addArgument('cdnPath', InputArgument::OPTIONAL, 'The CDN path to copy a file to.');

    }//end configure()


    /**
     * Make sure that the system name is set.
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inProject($input, $output);

        $projectDir     = Libs\FileSystem::getProjectDir();
        $this->storeDir = $projectDir.'/CDN/';
        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
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
        try {
            $cpPath  = $input->getArgument('cpPath');
            $cdnPath = ($input->getArgument('cdnPath') ?? null);

            if ($cdnPath === null) {
                Libs\FileSystem::mkdir($this->storeDir.$cpPath, true);
            } else {
                copy($cpPath, $this->storeDir.$cdnPath);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class