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
     * Initialize's some components for us to use.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        ini_set('error_log', dirname(__DIR__, 6).'/simulator/error_log');
        $this->style = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

    }//end initialize()


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
            $simPath = '/vendor/perspective/simulator';
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
        }//end if

        $project = str_replace('/', '\\', $project);
        \PerspectiveSimulator\Bootstrap::load($project);
        $input->setOption('project', str_replace('\\', '/', $project));
        $this->simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return true;

    }//end inProject()


    /**
     * Parses the diff array.
     *
     * @param InputInterface $input The console input object.
     * @param array          $diff  Diff array.
     *
     * @return array
     */
    final public function parseDiff(InputInterface $input, array $diff)
    {
        $changes = [];

        foreach ($diff as $change) {
            $changeParts = preg_split('/\s{1}/', $change);
            $type        = $changeParts[0][0];
            $path        = $changeParts[1];
            $project     = $this->getProject($path);
            if ($project !== null) {
                if (isset($changes[$project]) === false) {
                    $changes[$project] = [];
                }

                if (isset($changes[$project][$type]) === false) {
                    $changes[$project][$type] = [];
                }

                if (strpos($path, 'API') !== false) {
                    if (isset($changes[$project][$type]['API']) === false) {
                        $changes[$project][$type]['API'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['API'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['API'][] = $path;
                    }
                } else if (strpos($path, 'App') !== false) {
                    if (isset($changes[$project][$type]['App']) === false) {
                        $changes[$project][$type]['App'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['App'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['App'][] = $path;
                    }
                } else if (strpos($path, 'CDN') !== false) {
                    if (isset($changes[$project][$type]['CDN']) === false) {
                        $changes[$project][$type]['CDN'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['CDN'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['CDN'][] = $path;
                    }
                } else if (strpos($path, 'CustomTypes') !== false) {
                    if (isset($changes[$project][$type]['CustomTypes']) === false) {
                        $changes[$project][$type]['CustomTypes'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['CustomTypes'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['CustomTypes'][] = $path;
                    }
                } else if (strpos($path, 'Properties') !== false) {
                    if (isset($changes[$project][$type]['Properties']) === false) {
                        $changes[$project][$type]['Properties'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['Properties'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['Properties'][] = $path;
                    }
                } else if (strpos($path, 'Queues') !== false) {
                    if (isset($changes[$project][$type]['Queues']) === false) {
                        $changes[$project][$type]['Queues'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['Queues'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['Queues'][] = $path;
                    }
                } else if (strpos($path, 'Stores') !== false) {
                    if (isset($changes[$project][$type]['Stores']) === false) {
                        $changes[$project][$type]['Stores'] = [];
                    }

                    if (strpos($path, 'Data') !== false) {
                        if (isset($changes[$project][$type]['Stores']['Data']) === false) {
                            $changes[$project][$type]['Stores']['Data'] = [];
                        }

                        if ($type === 'R') {
                            $changes[$project][$type]['Stores']['Data'][] = [
                                'from' => $path,
                                'to'   => $changeParts[2],
                            ];
                        } else {
                            $changes[$project][$type]['Stores']['Data'][] = $path;
                        }
                    } else if (strpos($path, 'User') !== false) {
                        if (isset($changes[$project][$type]['Stores']['User']) === false) {
                            $changes[$project][$type]['Stores']['User'] = [];
                        }

                        if ($type === 'R') {
                            $changes[$project][$type]['Stores']['User'][] = $changeParts[2];
                        } else {
                            $changes[$project][$type]['Stores']['User'][] = $path;
                        }
                    }//end if
                } else {
                    if (isset($changes[$project][$type]['other']) === false) {
                        $changes[$project][$type]['other'] = [];
                    }

                    if ($type === 'R') {
                        $changes[$project][$type]['other'][] = $changeParts[2];
                    } else {
                        $changes[$project][$type]['other'][] = $path;
                    }
                }//end if
            }//end if
        }//end foreach

        $filterProject = ($input->getOption('project') ?? '');
        $filterProject = ltrim($filterProject, '=');
        $filterProject = str_replace('\\', '/', $filterProject);
        if (empty($filterProject) === true) {
            return $changes;
        } else if (isset($changes[$filterProject]) === true) {
            $changes = array_filter(
                $changes,
                function ($a) use ($filterProject) {
                    return $filterProject === $a;
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $changes;

    }//end parseDiff()


    /**
     * Gets the project for the change from the path.
     *
     * @param string $path Path of the change.
     *
     * @return string
     */
    final public function getProject(string $path)
    {
        $found    = null;
        $projects = Libs\Util::jsonDecode(file_get_contents(Libs\FileSystem::getSimulatorDir().'/projects.json'));
        foreach ($projects as $project => $projectPath) {
            $projectPath = ltrim(str_replace(Libs\FileSystem::getExportDir(), '', $projectPath), '/');
            if (strpos($path, $projectPath) === 0) {
                $found = $project;
                break;
            }
        }

        return $found;

    }//end getProject()


    /**
     * Logs upgrade instructionsfor when the cli adds/deletes/renames files.
     *
     * @param string $action The action performed.
     * @param string $type   The type being changed, eg, CustomDataRecord, Property etc.
     * @param array  $data   Array of data.
     *
     * @return void
     */
    final public function logChange(string $action, string $type, array $data)
    {
        $tags = Libs\Git::getTags();
        if (empty($tags) === true) {
            // Must be in predepolyment.
            return;
        }

        $changeLog = str_replace('src', '', Libs\FileSystem::getProjectDir()).'update.json';
        $tasks     = ['current' => []];

        if (file_exists($changeLog) === true) {
            $tasks = Libs\Util::jsonDecode(file_get_contents($changeLog));
        }

        if (isset($tasks['current'][$action]) === false) {
            $tasks['current'][$action] = [];
        }

        if (isset($tasks['current'][$action][$type]) === false) {
            $tasks['current'][$action][$type] = [];
        }

        $newRow = true;
        foreach ($tasks['current'][$action][$type] as $key => $value) {
            if ($value === $data['from']) {
                $newRow = false;
                $tasks['current'][$action][$type][$key] = $data['to'];
            }
        }

        if ($newRow === true) {
            $tasks['current'][$action][$type][$data['from']] = $data['to'];
        }

        file_put_contents($changeLog, Libs\Util::jsonEncode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    }//end logChange()


}//end class
