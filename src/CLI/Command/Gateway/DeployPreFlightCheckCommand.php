<?php
/**
 * DeployPreFlightCheckCommand class for Perspective Simulator CLI.
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

use \PerspectiveSimulator\Libs;

/**
 * DeployPreFlightCheckCommand Class
 */
class DeployPreFlightCheckCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Commands name.
     *
     * @var string
     */
    protected static $defaultName = 'gateway:deploy:preflightchecks';

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
        'queues',
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

        if (file_exists($projectDir.'/CustomTypes/Data/DataRecord.php') === true) {
            throw new \Exception('Unable to have a Custom Data Type called DataRecord');
        }

        if (file_exists($projectDir.'/CustomTypes/User/User.php') === true) {
            throw new \Exception('Unable to have a Custom User Type called User');
        }

        if (file_exists($projectDir.'/CustomTypes/User/Group.php') === true) {
            throw new \Exception('Unable to have a Custom User Type called Group');
        }

    }//end execute()


}//end class
