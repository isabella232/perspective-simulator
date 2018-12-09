<?php
/**
 * Project class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

require_once dirname(__FILE__).'/CommandTrait.inc';

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\CLI\Prompt;
use \PerspectiveSimulator\CLI\Terminal;
use \PerspectiveSimulator\Exceptions\CLIException;

/**
 * Project Class
 */
class Project
{
    use CommandTrait;

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


    /**
     * Constructor function.
     *
     * @param string $action The action we are going to perfom.
     * @param array  $args   An array of arguments to be used.
     *
     * @return void
     */
    public function __construct(string $action, array $args)
    {
        $exportDir      = Libs\FileSystem::getExportDir();
        $this->storeDir = $exportDir.'/projects/';
        $this->setArgs($action, $args);

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end __construct()


    /**
     * Generates the namespace from the name.
     *
     * @param string $name The name we are going to convert to namespace format.
     *
     * @return string
     */
    private function generateNamespace(string $name)
    {
        $namespace = str_replace(' ', '', $name);
        $namespace = preg_replace('/[^a-zA-Z0-9\_\-]/', '', $namespace);
        $namespace = preg_replace('/-+/', '', $namespace);

        $this->validateProjectNamespace($namespace);

        return $namespace;

    }//end generateNamespace()

    /**
     * Sets the args array.
     *
     * @param string $action Action that will be performed later.
     * @param array  $args   The arguments to be set.
     *
     * @return void
     */
    private function setArgs(string $action, array $args)
    {
        switch ($action) {
            case 'add':
                $this->args['name']      = ($args[0] ?? Prompt::textInput('Project name'));
                $this->args['namespace'] = ($args[1] ?? Prompt::textInput('Project namespace', $this->generateNamespace($this->args['name'])));
                $this->args['path']      = strtolower(
                    ($args[3] ?? Prompt::textInput('Project path', Libs\Web::makeValidWebPathString(strtolower($this->args['name']))))
                );
                $this->args['type']      = 'api';
            break;

            case 'delete':
                $this->args['namespace'] = ($args[0] ?? Prompt::textInput('Project namespace'));
            break;

            case 'update':
                $this->args['namespace'] = ($args[0] ?? Prompt::textInput('Project namespace'));
                $this->args['setting']   = ($args[1] ?? Prompt::textInput('Project setting'));
                $this->args['value']     = ($args[2] ?? Prompt::textInput('Value'));

                if ($this->args['setting'] === 'url') {
                    $message   = 'Select URL Type';
                    $inputText = 'URL Types (default: 1):';
                    $options   = ['api', 'cdn', 'delete'];
                    $this->args['urlType'] = ($args[3] ?? Prompt::optionList($message, $options, $inputText));
                }
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Validates the namespace of a project.
     *
     * @param string $namespace The namespace string.
     *
     * @return void
     * @throws CLIException When namespace is invalid.
     */
    private function validateProjectNamespace(string $namespace)
    {
        if (is_dir($this->storeDir.$namespace) === true) {
            throw new CLIException(sprintf('Duplicate project namespace (%s).', $namespace));
        }

        // Check php namspace.
        $syntaxRes = Libs\Util::checkPHPSyntax('<?php'."\n".'namespace '.$namespace.'; ?>');
        if ($syntaxRes !== true) {
            throw new CLIException(sprintf('Invalid project namespace (%s).', $namespace));
        }

    }//end validateProjectNamespace()


    /**
     * Validates the path of a project.
     *
     * @param string $path The path for the project.
     *
     * @return void
     * @throws CLIException When path is invalid.
     */
    private function validateProjectPath(string $path)
    {
        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.' && file_exists($path.'/project.json') === true) {
                $settings = Libs\Util::jsonDecod(file_get_contents($path.'/project.json'));
                foreach ($settings['url'] as $url) {
                    if ($url['type'] === 'author') {
                        $urlParts = explode('/', $url['url']);
                        $baseURL  = array_shift($urlParts);
                        $urlPath  = implode('/', $urlParts);
                        if (strtolower($urlPath) === $path) {
                            throw new CLIException(sprintf('Duplicate project path (%s)', $path));
                        }
                    }
                }
            }//end if
        }//end foreach

    }//end validateProjectPath()


    /**
     * Adds a new specification file, changes to this will affect update when path is given.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function add()
    {
        if (empty($this->args['name']) === true) {
            throw new CLIException('Projects name is required.');
        }

        try {
            $this->validateProjectNamespace($this->args['namespace']);
            $this->validateProjectPath($this->args['path']);

            $settingsFile   = Libs\FileSystem::getExportDir().'/system_info.json';
            $systemSettings = ['systemURL' => ''];
            if (file_exists($settingsFile) === true) {
                $systemSettings = Libs\Util::jsonDecode(
                    file_get_contents($settingsFile)
                );
            }

            $settings = [
                'name' => $this->args['name'],
                'type' => $this->args['type'],
                'urls' => [
                    [
                        'url'  => $systemSettings['systemURL'].'/'.$this->args['path'],
                        'type' => 'author',
                    ],
                ],
            ];

            $projectDir = Libs\FileSystem::getProjectDir($this->args['namespace']);
            Libs\FileSystem::mkdir($projectDir, true);
            file_put_contents($projectDir.'/project.json', Libs\Util::jsonEncode($settings));

            // Install project for the simulator.
            $simulatorDir       = Libs\FileSystem::getSimulatorDir();
            $project            = $this->args['namespace'];
            $GLOBALS['project'] = $project;

            if (is_dir($simulatorDir.'/'.$project) === false) {
                Libs\FileSystem::mkdir($simulatorDir.'/'.$project);
            }

            $projectKey = \PerspectiveSimulator\Authentication::generateSecretKey();

            $storageDir = Libs\FileSystem::getStorageDir($project);
            if (is_dir($storageDir) === false) {
                Libs\FileSystem::mkdir($storageDir);
            }

            $folders = ['API', 'API/Operations', 'App', 'CDN', 'CustomTypes', 'Properties', 'Stores', 'Queues'];
            foreach ($folders as $folder) {
                if (is_dir($projectDir.'/'.$folder) === false) {
                    Libs\FileSystem::mkdir($projectDir.'/'.$folder);
                }
            }

            $testDir = Libs\FileSystem::getExportDir().'/projects/'.$this->args['namespace'].'/tests';
            if (is_dir($testDir) === false) {
                Libs\FileSystem::mkdir($testDir);
            }

            \PerspectiveSimulator\API::installAPI($project);
            \PerspectiveSimulator\Queue\Queue::installQueues($project);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end add()


    /**
     * Deletes a project.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function delete()
    {
        if (empty($this->args['namespace']) === true) {
            throw new CLIException('Projects namespace is required.');
        }

        try {
            $msg = Terminal::formatText(
                sprintf('This will delete the %s project.', $this->args['namespace']),
                ['bold']
            );
            $this->confirmAction($msg);

            $projectDir = Libs\FileSystem::getExportDir().'/projects/'.$this->args['namespace'];
            if (is_dir($projectDir) === false) {
                throw new CLIException(sprintf('Project (%s) doesn\'t exist', $this->args['namespace']));
            }

            Libs\FileSystem::delete($projectDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end delete()


    /**
     * Updates a project setting
     *
     * @return void
     * @throws CLIException When invalid.
     */
    public function update()
    {
        if (empty($this->args['namespace']) === true) {
            throw new CLIException('Projects namespace is required.');
        }

        try {
            $settingsFile   = Libs\FileSystem::getExportDir().'/system_info.json';
            $systemSettings = ['systemURL' => ''];
            if (file_exists($settingsFile) === true) {
                $systemSettings = Libs\Util::jsonDecode(
                    file_get_contents($settingsFile)
                );
            }

            switch ($this->args['setting']) {
                case 'namespace':
                    $this->validateProjectNamespace($this->args['value']);
                    Libs\FileSystem::move(
                        $this->storeDir.$this->args['namespace'],
                        $this->storeDir.$this->args['value']
                    );
                break;

                case 'name':
                    if (empty($this->args['value']) === true) {
                        throw new CLIException('Project name is required.');
                    }

                    $project          = Libs\FileSystem::getExportDir().'/projects/'.$this->args['namespace'].'/src/project.json';
                    $settings         = Libs\Util::jsonDecode(file_get_contents($project));
                    $settings['name'] = $this->args['value'];
                    file_put_contents($project, Libs\Util::jsonEncode($settings));
                break;

                case 'path':
                    $this->validateProjectPath($this->args['value']);
                    $project  = Libs\FileSystem::getExportDir().'/projects/'.$this->args['namespace'].'/src/project.json';
                    $settings = Libs\Util::jsonDecode(file_get_contents($project));

                    foreach ($settings['urls'] as &$url) {
                        if ($url['type'] === 'author') {
                            $url['url'] = $systemSettings['systemURL'].'/'.$this->args['value'];
                        }
                    }

                    file_put_contents($project, Libs\Util::jsonEncode($settings));
                break;

                case 'url':
                    $project  = Libs\FileSystem::getExportDir().'/projects/'.$this->args['namespace'].'/src/project.json';
                    $settings = Libs\Util::jsonDecode(file_get_contents($project));

                    $updated = false;
                    foreach ($settings['urls'] as $idx => &$url) {
                        if ($this->args['urlType'] === 'delete') {
                            if (strtolower($url['url']) === strtolower($this->args['value'])) {
                                $msg = Terminal::formatText(
                                    sprintf('This will remove the url %s from the project.', $url['url']),
                                    ['bold']
                                );
                                $this->confirmAction($msg);

                                unset($settings['urls'][$idx]);
                                $updated = true;
                                break;
                            }
                        } else {
                            if (strtolower($url['type']) === $this->args['urlType']) {
                                $url['url'] = $this->args['value'];
                                $updated = true;
                                break;
                            }
                        }
                    }

                    if ($updated === false) {
                        $settings['urls'][] = [
                            'url'  => $this->args['value'],
                            'type' => $this->args['urlType'],
                        ];
                    }

                    file_put_contents($project, Libs\Util::jsonEncode($settings));
                break;

                default:
                throw new CLIException(sprintf('Invalid project setting: %s', $this->args['setting']));
            }
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end update()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    public function printHelp(string $filter=null)
    {
        $actions = [
            'add'    => [
                'action'      => 'perspective [-p] add project',
                'description' =>'Creates a new project.',
                'arguments'   => [
                    'required' => [
                        'name'      => 'The name for the new Project.',
                        'namespace' => 'The namespace for the new Project.',
                        'path'      => 'The web path for the new Project.',
                    ],
                ],
            ],
            'delete' => [
                'action'      => 'perspective [-p] delete project',
                'description' => 'Deletes a project.',
                'arguments'   => [
                    'required' => [
                        'namespace' => 'The namespace of the Project we are deleting.',
                    ],
                ],
            ],
            'update' => [
                'action'      => 'perspective [-p] update project',
                'description' => 'Updates a project setting.',
                'arguments'   => [
                    'required' => [
                        'namespace' => 'The namespace of the Project we are updating.',
                        'setting'   => 'The setting we are updating.',
                        'value'     => 'The new value for the setting',
                    ],
                ],
            ],
        ];

        if ($filter !== null) {
            $actions = array_filter(
                $actions,
                function ($a) use ($filter) {
                    return $a === $filter;
                },
                ARRAY_FILTER_USE_KEY
            );

            Terminal::printLine(
                Terminal::padText(
                    'Usage for: '.$actions[$filter]['action']
                )
            );
        } else {
            Terminal::printLine(
                Terminal::padText(
                    'Usage for: perspective <action> project <arguments>'
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
