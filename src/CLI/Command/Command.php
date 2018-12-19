<?php
/**
 * Command class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use \PerspectiveSimulator\Libs;

/**
 * Command Class
 */
class Command extends \Symfony\Component\Console\Command\Command
{


    /**
     * Tests we have a project.
     *
     * @return boolean
     */
    final public function inProject(InputInterface $input, OutputInterface $output)
    {
        $project = ($input->getOption('project') ?? '');
        $project = ltrim($project, '=');
        if (empty($project) === true) {
            // Workout the current project and if the simulator is installed so we can run our actions.
            $simPath = '/vendor/Perspective/Simulator';
            $cwd     = getcwd();
            $proot   = $cwd;
            while (is_dir($proot.$simPath) === false) {
                $proot = dirname($proot);
                if ($proot === '/') {
                    $output->writeln('<error>Unable to find Perspective Simulator</error>');
                    exit(1);
                }
            }

            $installedProjects = Libs\Util::jsonDecode(file_get_contents(Libs\FileSystem::getSimulatorDir().'/projects.json'));
            $projects          = [];
            foreach ($installedProjects as $proj => $path) {
                $baseProjectPath = str_replace('/src', '', $path);
                if (strrpos($cwd, $baseProjectPath) !== false) {
                    $project = str_replace('/', '\\', $proj);
                    break;
                }
                $projects[] = str_replace('/', '\\', $proj);
            }//end foreach

            if (empty($project) === true) {
                $helper   = $this->getHelper('question');
                $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                    'Please select which project you want to perform the action in (default: <comment>0</comment>)',
                    $projects,
                    0
                );

                $project = $helper->ask($input, $output, $question);
                $output->writeln('The action will be performed on <info>'.$project.'</info>');
            }
        }

        $project = str_replace('/', '\\', $project);
        \PerspectiveSimulator\Bootstrap::load($project);

        return true;

    }//end inProject();


}//end class