<?php
/**
 * DiffCommand class for Perspective Simulator CLI.
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

use \PerspectiveSimulator\Libs;

/**
 * DiffCommand Class
 */
class DiffCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Commands name.
     *
     * @var string
     */
    protected static $defaultName = 'simulator:diff';

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
        $this->setDescription('Detects changes between versions.');
        $this->setHelp('Detectst the changes between different versions of projects.');
        $this->addArgument('from', InputArgument::REQUIRED, 'The commit hash or tag name to get the changes from.');
        $this->addArgument(
            'to',
            InputArgument::OPTIONAL,
            'The commit hash or tag name to get the changes to, if not provided will use current HEAD.'
        );

    }//end configure()


    /**
     * Parses the diff array.
     *
     * @param InputInterface $input The console input object.
     * @param array          $diff  Diff array.
     *
     * @return array
     */
    private function parseDiff(InputInterface $input, array $diff)
    {
        $changes = [];

        foreach ($diff as $change) {
            $changeParts = preg_split('/\s{1}/', $change);
            $type        = $changeParts[0];
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

                    $changes[$project][$type]['API'][] = $path;
                } else if (strpos($path, 'App') !== false) {
                    if (isset($changes[$project][$type]['App']) === false) {
                        $changes[$project][$type]['App'] = [];
                    }

                    $changes[$project][$type]['App'][] = $path;
                } else if (strpos($path, 'CDN') !== false) {
                    if (isset($changes[$project][$type]['CDN']) === false) {
                        $changes[$project][$type]['CDN'] = [];
                    }

                    $changes[$project][$type]['CDN'][] = $path;
                } else if (strpos($path, 'CustomTypes') !== false) {
                    if (isset($changes[$project][$type]['CustomTypes']) === false) {
                        $changes[$project][$type]['CustomTypes'] = [];
                    }

                    $changes[$project][$type]['CustomTypes'][] = $path;
                } else if (strpos($path, 'Properties') !== false) {
                    if (isset($changes[$project][$type]['Properties']) === false) {
                        $changes[$project][$type]['Properties'] = [];
                    }

                    $changes[$project][$type]['Properties'][] = $path;
                } else if (strpos($path, 'Queues') !== false) {
                    if (isset($changes[$project][$type]['Queues']) === false) {
                        $changes[$project][$type]['Queues'] = [];
                    }

                    $changes[$project][$type]['Queues'][] = $path;
                } else if (strpos($path, 'Stores') !== false) {
                    if (isset($changes[$project][$type]['Stores']) === false) {
                        $changes[$project][$type]['Stores'] = [];
                    }

                    if (strpos($path, 'Data') !== false) {
                        if (isset($changes[$project][$type]['Stores']['Data']) === false) {
                            $changes[$project][$type]['Stores']['Data'] = [];
                        }

                        $changes[$project][$type]['Stores']['Data'][] = $path;
                    } else if (strpos($path, 'User') !== false) {
                        if (isset($changes[$project][$type]['Stores']['User']) === false) {
                            $changes[$project][$type]['Stores']['User'] = [];
                        }

                        $changes[$project][$type]['Stores']['User'][] = $path;
                    }
                } else {
                    if (isset($changes[$project][$type]['other']) === false) {
                        $changes[$project][$type]['other'] = [];
                    }

                    $changes[$project][$type]['other'][] = $path;
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
    private function getProject(string $path)
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
     * Executes the create new project command.
     *
     * @param InputInterface  $input  Console input object.
     * @param OutputInterface $output Console output object.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $to   = ($input->getArgument('to') ?? null);
        $diff = Libs\Git::getDiff($from, $to);

        $changes = $this->parseDiff($input, $diff);

        foreach ($changes as $project => $changeData) {
            $style = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
            $style->title($project);
            foreach ($changeData as $type => $change) {
                $gitType = '';
                switch ($type) {
                    case 'A':
                        $gitType = 'Added';
                    break;

                    case 'C':
                        $gitType = 'Copied';
                    break;

                    case 'D':
                        $gitType = 'Deleted';
                    break;

                    case 'M':
                        $gitType = 'Modified';
                    break;

                    case 'R':
                        $gitType = 'Renamed';
                    break;

                    case 'T':
                        $gitType = 'Type Change';
                    break;

                    case 'U':
                        $gitType = 'Unmerged';
                    break;

                    case 'X':
                        $gitType = 'Unknown';
                    break;

                    case 'B':
                        $gitType = 'Pairing Broken';
                    break;

                    default:
                        $gitType = '';
                    break;
                }

                $style->section('Following changes detected as bieing: '.$gitType);

                foreach ($change as $system => $paths) {
                    if ($system === 'Stores') {
                        foreach ($paths as $store => $storePaths) {
                            $style->block($store.' Stores', null, 'fg=yellow', ' ! ');
                            $style->listing($storePaths);
                        }
                    } else {
                        $style->block($system, null, 'fg=yellow', ' ! ');
                        $style->listing($paths);
                    }

                }
            }
        }

    }//end execute()


}//end class
