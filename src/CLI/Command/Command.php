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
        $project = ($input->getOption('project') ?? null);
        $project = ltrim($project, '=');
        if ($project === null) {
            // Workout the current project and if the simulator is installed so we can run our actions.
            $simPath      = '/vendor/Perspective/Simulator';
            $cwd          = getcwd();
            $proot        = $cwd;
            $project      = null;
            $prevBasename = null;
            while (file_exists($proot.$simPath) === false) {
                if ($project === null) {
                    $prevBasename = basename($proot);
                }

                $proot = dirname($proot);
                if ($proot === '/') {
                    throw new \Expception('Perspective command must be run in a Perspective export.');
                }

                if (basename($proot) === 'projects' && $project === null) {
                    $project = $prevBasename;
                }
            }

            if ($project === null) {
                $projects    = [];
                $projectPath = Libs\FileSystem::getExportDir().'/projects/';
                $projectDirs = scandir($projectPath);
                foreach ($projectDirs as $proj) {
                    $path = $projectPath.$project;
                    if (is_dir($path) === true && $proj[0] !== '.') {
                        $projects[] = $proj;
                    }
                }//end foreach

                $helper   = $this->getHelper('question');
                $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                    'Please select which project you want to perform the action in (default: 0)',
                    $projects,
                    0
                );

                $project = $helper->ask($input, $output, $question);
                $output->writeln('You have just selected: '.$project);
            }
        }

        \PerspectiveSimulator\Bootstrap::load($project);

        return true;

    }//end inProject();


}//end class