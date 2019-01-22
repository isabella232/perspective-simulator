<?php
/**
 * PreFlightCheckCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Deployment;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * PreFlightCheckCommand Class
 */
class PreFlightCheckCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Commands name.
     *
     * @var string
     */
    protected static $defaultName = 'deployment:preflightchecks';

    /**
     * Array of allowed directory names directly under src.
     *
     * @var array
     */
    private $allowedDirs = [
        'api',
        'app',
        'cdn',
        'customtypes',
        'properties',
        'queues',
        'stores',
    ];


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Validates project for deployment.');
        $this->setHelp('Validates project for deployment.');

    }//end configure()


    /**
     * Make sure that the system name is set.
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
     * @param InputInterface  $input  Console input object.
     * @param OutputInterface $output Console output object.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectDir = Libs\FileSystem::getProjectDir();
        $dirs = glob($projectDir.'/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $folder = strtolower(basename($dir));
            if (in_array($folder, $this->allowedDirs) === false) {
                throw new \Exception(sprintf('Invaild folder "%s" detected', $dir));
            }
        }

    }//end execute()


}//end class
