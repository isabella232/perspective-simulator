<?php
/**
 * Project class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Project;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'project:add';

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
        $this->setDescription('Adds a new Project to the export.');
        $this->setHelp('Creates a new Project.');
        $this->addArgument('namespace', InputArgument::REQUIRED);

    }//end configure()


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
     * Validates the namespace of a project.
     *
     * @param string $namespace The namespace string.
     *
     * @return void
     * @throws Exception When namespace is invalid.
     */
    private function validateProjectNamespace(string $namespace)
    {
        if (is_dir($this->storeDir.$namespace) === true) {
            throw new \Exception(sprintf('Duplicate project namespace (%s).', $namespace));
        }

        // Check php namspace.
        $syntaxRes = Libs\Util::checkPHPSyntax('<?php'."\n".'namespace '.$namespace.'; ?'.'>');
        if ($syntaxRes !== true) {
            throw new \Exception(sprintf('Invalid project namespace (%s).', $namespace));
        }

    }//end validateProjectNamespace()


    /**
     * Validates the path of a project.
     *
     * @param string $path The path for the project.
     *
     * @return void
     * @throws Exception When path is invalid.
     */
    private function validateProjectPath(string $path)
    {
        $projectPath = Libs\FileSystem::getExportDir().'/projects/';
        $projectDirs = scandir($projectPath);
        foreach ($projectDirs as $project) {
            $path = $projectPath.$project;
            if (is_dir($path) === true && $project[0] !== '.' && file_exists($path.'/project.json') === true) {
                $settings = Libs\Util::jsonDecode(file_get_contents($path.'/project.json'));
                foreach ($settings['url'] as $url) {
                    if ($url['type'] === 'author') {
                        $urlParts = explode('/', $url['url']);
                        $baseURL  = array_shift($urlParts);
                        $urlPath  = implode('/', $urlParts);
                        if (strtolower($urlPath) === $path) {
                            throw new \Exception(sprintf('Duplicate project path (%s)', $path));
                        }
                    }
                }
            }//end if
        }//end foreach

    }//end validateProjectPath()


    /**
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $exportDir      = Libs\FileSystem::getExportDir();
        $this->storeDir = $exportDir.'/projects/';
        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

        $helper = $this->getHelper('question');

        if (empty($input->getArgument('namespace')) === true) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter a Project namespace: ');
            $namespace  = $helper->ask($input, $output, $question);
            $input->setArgument('namespace', $namespace);
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
        $this->style->title('Creating new project');

        try {
            $namespace = strtolower($input->getArgument('namespace'));
            $this->validateProjectNamespace($namespace);

            $settingsFile   = Libs\FileSystem::getExportDir().'/system_info.json';
            $systemSettings = ['systemURL' => ''];
            if (file_exists($settingsFile) === true) {
                $systemSettings = Libs\Util::jsonDecode(
                    file_get_contents($settingsFile)
                );
            }

            $settings = [
                'name' => $namespace,
                'type' => 'api',
                'urls' => [
                    [
                        'url'  => '',
                        'type' => '',
                    ],
                ],
            ];

            $projectDir = Libs\FileSystem::getProjectDir($namespace);
            Libs\FileSystem::mkdir($projectDir, true);
            file_put_contents($projectDir.'/project.json', Libs\Util::jsonEncode($settings));

            $composer = [
                'name'        => strtolower(str_replace('\\', '/', $namespace)),
                'description' => 'Project for '.$namespace,
            ];
            file_put_contents(dirname($projectDir).'/composer.json', Libs\Util::jsonEncode($composer));

            $folders = [
                'API',
                'API/Operations',
                'App',
                'CDN',
                'CustomTypes',
                'Properties',
                'Stores',
                'Queues',
                'web',
                'web/handlers',
                'web/views'
            ];
            foreach ($folders as $folder) {
                if (is_dir($projectDir.'/'.$folder) === false) {
                    Libs\FileSystem::mkdir($projectDir.'/'.$folder);
                }
            }

            $testDir = dirname($projectDir).'/tests';
            if (is_dir($testDir) === false) {
                Libs\FileSystem::mkdir($testDir);
            }

            // Install project for the simulator.
            $this->getApplication()->find('simulator:install')->run($input, $output);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

        $output->writeln('New project created: '.$name);

    }//end execute()


}//end class
