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

    /**
     * The name of the command
     *
     * @var string
     */
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
        $this->setDescription('Creates a new Project.');
        $this->setHelp('Adds a new Project to the export.');
        $this->addArgument('packageName', InputArgument::REQUIRED, 'The package name for the project');
        $this->addArgument('namespace', InputArgument::REQUIRED, 'The namespace for the project');

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
     * @throws \Exception When namespace is invalid.
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
     * Interact with the console command.
     *
     * @param InputInterface  $input  Symfony consoles input interface.
     * @param OutputInterface $output Symfony consoles output interface.
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

        if (empty($input->getArgument('packageName')) === true) {
            $question    = new \Symfony\Component\Console\Question\Question('Please enter a package name: ');
            $packageName = $helper->ask($input, $output, $question);
            $input->setArgument('packageName', $packageName);
        }

        if (empty($input->getArgument('namespace')) === true) {
            $question  = new \Symfony\Component\Console\Question\Question('Please enter a Project namespace: ');
            $namespace = $helper->ask($input, $output, $question);
            $input->setArgument('namespace', $namespace);
        }

    }//end interact()


    /**
     * Executes the create new project command.
     *
     * @param InputInterface  $input  Symfony consoles input interface.
     * @param OutputInterface $output Symfony consoles output interface.
     *
     * @return void
     * @throws \Exception When error occurs.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->style->title('Adding new project to local environment');

        try {
            $namespace = $input->getArgument('namespace');
            $this->validateProjectNamespace($namespace);
            $packageName = strtolower(str_replace('\\', '/', $input->getArgument('packageName')));

            $composer = [
                'name'        => $packageName,
                'description' => 'Project for '.$packageName,
                'autoload'    => [
                    'psr-4' => [$namespace.'\\' => 'src/'],
                ],
            ];

            $projectDir = Libs\FileSystem::getProjectDir($packageName);
            Libs\FileSystem::mkdir($projectDir, true);
            file_put_contents(
                dirname($projectDir).'/composer.json',
                Libs\Util::jsonEncode($composer, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            );

            $folders = [
                'API',
                'API/Operations',
                'App',
                'CDN',
                'CustomTypes',
                'Queues',
            ];
            foreach ($folders as $folder) {
                if (is_dir($projectDir.'/'.$folder) === false) {
                    Libs\FileSystem::mkdir($projectDir.'/'.$folder);
                }
            }

            $stores = $projectDir.'/stores.json';
            if (file_exists($stores) === false) {
                file_put_contents(
                    $stores,
                    Libs\Util::jsonEncode(
                        [
                            'stores' => [
                                'data' => [],
                                'user' => [],
                            ],
                            'references' => [],
                        ]
                    )
                );
            }

            $testDir = dirname($projectDir).'/tests';
            if (is_dir($testDir) === false) {
                Libs\FileSystem::mkdir($testDir);
            }

            // Install project for the simulator.
            $updateCommand = $this->getApplication()->find('simulator:update');
            $updateArgs    = ['command' => 'simulator:update'];

            $updateInput = new \Symfony\Component\Console\Input\ArrayInput($updateArgs);
            $returnCode  = $updateCommand->run($updateInput, $output);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

        $this->style->section('New Project created');
        $this->style->note('Pacakge Name: '.$packageName);
        $this->style->note('Namespace: '.$namespace);
        $this->style->note('Project direcrtory: '.$projectDir);

    }//end execute()


}//end class
