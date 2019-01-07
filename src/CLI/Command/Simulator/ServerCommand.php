<?php
/**
 * ServerCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Simulator;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * ServerCommand Class
 */
class ServerCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'simulator:server';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Starts the PHP webserver for the simulator.');
        $this->setHelp('Starts the PHP webserver for the simulator.');
        $this->addArgument('host', InputArgument::OPTIONAL, 'Optional IP and Port to listen on, default 0.0.0.0:8000.');
        $this->addOption(
            'latency',
            'l',
            InputOption::VALUE_NONE,
            'Flag to introduce latency between 0 and 2 seconds on each request.',
            null
        );

        $this->addOption(
            'failure',
            'f',
            InputOption::VALUE_NONE,
            'Flag to simulate failed requests, this is randomly done based on what the latency delay would be.',
            null
        );

    }//end configure()


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
        // Workout the current project and if the simulator is installed so we can run our actions.
        $simPath = '/vendor/perspective/simulator';
        $cwd     = getcwd();
        $proot   = $cwd;
        $sim     = true;
        while (file_exists($proot.$simPath) === false) {

            $proot = dirname($proot);
            if ($proot === '/') {
                $sim = false;
                break;
            }
        }

        if ($sim === false) {
            // Not in the simulator directory and somehow we got here, so don't start the server.
            return;
        }

        if (file_exists($proot.'/simulator/router-settings.json') === true) {
            \PerspectiveSimulator\Libs\FileSystem::delete($proot.'/simulator/router-settings.json');
        }

        $latency = ($input->getOption('latency') ?? false);
        $failure = ($input->getOption('failure') ?? false);
        $host    = ($input->getArgument('host') ?? '0.0.0.0:8000');
        $style = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
        $style->title('Perspecitve Simulator running.');
        $style->section('listening on: http://'.$host);
        $style->block('Press Ctrl-C to quit.', null, 'fg=yellow', ' ! ');

        $routerData = [
            'latency' => $latency,
            'failure' => $failure,
        ];
        file_put_contents($proot.'/simulator/router-settings.json', \PerspectiveSimulator\Libs\Util::jsonEncode($routerData));

        $router = $proot.'/vendor/perspective/simulator/src/Requests/Router.php';
        exec('php -S '.$host.' '.$router);

    }//end execute()


}//end class
