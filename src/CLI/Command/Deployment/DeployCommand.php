<?php
/**
 * DeployCommand class for Perspective Simulator CLI.
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
 * DeployCommand Class
 */
class DeployCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Commands name.
     *
     * @var string
     */
    protected static $defaultName = 'deployment:deploy';

    /**
     * The directory where the data to be deployed will be prepared in.
     *
     * @var string
     */
    private $dataDir = '';

    /**
     * The data to be sent to gateway.
     *
     * @var array
     */
    private $data = [];

    /**
     * The progress bar object
     *
     * @var object
     */
    private $progressBar = null;


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Deploys the project.');
        $this->setHelp('Deploys a given project.');
        $this->addArgument('oldVersion', InputArgument::REQUIRED, 'The previous version number or commit ID.');
        $this->addArgument('newVersion', InputArgument::REQUIRED, 'The new version number eg: 0.0.1');

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

        $simulatorDir  = Libs\FileSystem::getSimulatorDir();
        $this->dataDir = $simulatorDir.'/deploy/'.str_replace('\\', DIRECTORY_SEPARATOR, ($input->getOption('project') ?? ''));
        if (is_dir($this->dataDir) === false) {
            Libs\FileSystem::mkdir($this->dataDir, true);
        } else {
            Libs\FileSystem::delete($this->dataDir);
        }

        $re      = '/^\d+(\.\d+)*$/';
        $matches = [];
        preg_match($re, $input->getArgument('newVersion'), $matches);
        if (empty($matches) === true) {
            $style = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
            $style->error('Invalid version number. Version number can only contain . or intergers.');
            exit(1);
        }

        // Run predepolyment check command.
        // Run the diff command so the user will know what changes are about to be made.
        $diffCommand = $this->getApplication()->find('deployment:diff');
        $diffArgs    = [
            'command' => 'deployment:diff',
            'from'    => $input->getArgument('oldVersion'),
        ];

        $diffInput  = new \Symfony\Component\Console\Input\ArrayInput($diffArgs);
        $returnCode = $diffCommand->run($diffInput, $output);

        $helper  = $this->getHelper('question');
        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'The above changes have been detected do you want to continue the deployment',
            false
        );

        if ($helper->ask($input, $output, $confirm) === false) {
            return;
        }

    }//end interact()


    /**
     * Executes the create new project command.
     *
     * @param InputInterface  $input  Console input object.
     * @param OutputInterface $output Console output object.
     *
     * @return void
     * @throws \Exception When unable to tar data.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from    = $input->getArgument('oldVersion');
        $version = $input->getArgument('newVersion');
        $diff    = Libs\Git::getDiff($from);
        $project = str_replace('\\', '/', $input->getOption('project'));
        $changes = $this->parseDiff($input, $diff);
        $changes = $changes[$project];

        $maxSteps          = 1;
        $this->progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, $maxSteps);
        $this->progressBar->setFormat(
            "%titleMessage%\n %current%/%max% [%bar%] <info>%progressMessage%</info> %percent:3s%% %memory:6s%\n"
        );
        $this->progressBar->setMessage('<comment>Preparing to deploy: '.$project.'</comment>', 'titleMessage');
        $this->progressBar->setMessage('', 'progressMessage');
        $this->progressBar->setBarCharacter('<comment>=</comment>');
        $this->progressBar->setEmptyBarCharacter('-');
        $this->progressBar->setProgressCharacter('>');
        $this->progressBar->start();

        foreach ($changes as $type => $changeData) {
            foreach ($changeData as $system => $paths) {
                if ($system === 'Stores') {
                    foreach ($paths as $store => $storePaths) {
                        $maxSteps = ($maxSteps + count($storePaths));
                        $this->progressBar->setMaxSteps($maxSteps);
                        $this->progressBar->setMessage('Gathering '.$store.' '.$system, 'progressMessage');
                        $this->progressBar->advance(0);
                        foreach ($paths as $path) {
                            $this->gatherData($type, $path, $store.'Store');
                            $this->progressBar->advance();
                        }
                    }
                } else {
                    $maxSteps = ($maxSteps + count($paths));
                    $this->progressBar->setMaxSteps($maxSteps);
                    $this->progressBar->setMessage('Gathering '.$system, 'progressMessage');
                    $this->progressBar->advance(0);
                    foreach ($paths as $path) {
                        $this->gatherData($type, $path, $system);
                        $this->progressBar->advance();
                    }
                }//end if
            }//end foreach
        }//end foreach

        file_put_contents(
            $this->dataDir.'/data.json',
            Libs\Util::jsonEncode(
                [
                    'project'    => $project,
                    'version'    => $version,
                    'passengers' => $this->data,
                ]
            )
        );
        $this->progressBar->setMessage('<comment>Preparing to send data</comment>', 'titleMessage');
        $this->progressBar->setMessage('', 'progressMessage');
        $maxSteps = ($maxSteps + 2);
        $this->progressBar->setMaxSteps($maxSteps);
        $this->progressBar->advance(0);

        $tarDir      = Libs\FileSystem::getSimulatorDir().'/deploy';
        $tarFilename = tempnam('/tmp/', 'deploy_data_'.str_replace('/', '_', $project));
        $tarCommand  = 'tar -jcf ';
        $tarCommand .= escapeshellarg($tarFilename);
        $tarCommand .= ' -C '.escapeshellarg($tarDir.'/'.$project);
        $tarCommand .= ' `ls -1 '.escapeshellarg($tarDir.'/'.$project).'`';
        $tarOutput   = [];
        $tarRC       = -1;

        $projectSrcDir = Libs\FileSystem::getProjectDir();
        $projectVenDir = str_replace('src', 'vendor', Libs\FileSystem::getProjectDir());
        exec('cp -r '.$projectSrcDir.' '.$tarDir.'/'.$project.'/src/');
        exec('cp -r '.$projectVenDir.' '.$tarDir.'/'.$project.'/vendor/');
        exec($tarCommand, $tarOutput, $tarRC);

        if ($tarRC !== 0) {
            throw new \Exception(
                'Unable to tar event, tar command \''.$tarCommand.'\' output: \''.implode(' ', $tarOutput).'\''
            );
        }

        $this->progressBar->advance();
        $versionFile = $tarDir.'/'.str_replace('/', '_', $project).'.tar.bz2';
        Libs\FileSystem::move($tarFilename, $versionFile);
        $this->progressBar->advance();

        $this->progressBar->setMessage('<comment>Sending deployment</comment>', 'titleMessage');
        $this->progressBar->advance(0);
        // send
        // 'fileContent'     => base64_encode(file_get_contents($versionFile)),
        // 'checksum'        => sha1_file($versionFile),
        // 'versionFileName' => str_replace('/', '_', $project).'.tar.bz2',
        $this->progressBar->advance();

        $this->progressBar->setMessage('', 'titleMessage');
        $this->progressBar->finish();

    }//end execute()


    /**
     * Gets the data ready.
     *
     * @param string $type   The type of change.
     * @param string $path   The path of the change.
     * @param string $system The system of the change eg, App, CustomType etc.
     *
     * @return void
     */
    private function gatherData(string $type, string $path, string $system)
    {
        $path = Libs\FileSystem::getExportDir().DIRECTORY_SEPARATOR.$path;
        $data = [];
        if ($system === 'App') {
            $action = 'app';
            $data   = [
                'source_code' => file_get_contents($path),
                'class_name'  => str_replace('.php', '', basename($path)),
            ];
        } else if ($system === 'API') {
            $data = [];
            if (strpos($path, 'Operations') !== false) {
                $action = 'apiOperation';
                $data['operation'] = str_replace('.php', '', basename($path));
            } else {
                $action = 'apiSpec';
            }

            $data['source_code'] = file_get_contents($path);
        } else if ($system === 'CDN') {
            $action  = 'cdnFile';
            $cdnPath = str_replace(Libs\FileSystem::getProjectDir().DIRECTORY_SEPARATOR.'CDN/', '', $path);
            // if (is_dir($this->dataDir.'/CDN/') === false) {
            //     Libs\FileSystem::mkdir($this->dataDir.'/CDN/', true);
            // }

            // if (in_array($type, ['A', 'C', 'M', 'R']) === true) {
            //     if (is_dir(dirname($this->dataDir.'/CDN/'.$cdnPath)) === false) {
            //         Libs\FileSystem::mkdir(dirname($this->dataDir.'/CDN/'.$cdnPath), true);
            //     }

            //     copy($path, $this->dataDir.'/CDN/'.$cdnPath);
            // }

            $data = ['path' => $cdnPath];
        } else if ($system === 'CustomTypes') {
            if (substr($path, -5) === '.json') {
                // We will get the custom type from the PHP class instead.
                return;
            }

            $action = 'customType';
            $data   = [
                'customTypeCode' => str_replace('.php', '', basename($path)),
                'source_code'    => file_get_contents($path),
            ];
        } else if ($system === 'Properties') {
            if (substr($path, -5) !== '.json') {
                return;
            }

            $action = 'property';
            $data   = Libs\Util::jsonDecode(file_get_contents($path));
            // if ($data['type'] === 'file' || $data['type'] === 'image') {
            //     if (is_dir($this->dataDir.'/Properties/') === false) {
            //         Libs\FileSystem::mkdir($this->dataDir.'/Properties/', true);
            //     }

            //     if (in_array($type, ['A', 'C', 'M', 'R']) === true) {
            //         copy($path, $this->dataDir.'/Properties/'.basename($path));
            //     }
            // }
        } else if ($system === 'Queues') {
            $action = 'queue';
            $data   = [
                'queue_name'  => str_replace('.php', '', basename($path)),
                'source_code' => file_get_contents($path),
            ];
        } else if ($system === 'DataStore' || $system === 'UserStore') {
            if (substr($path, -8) === '.gitKeep') {
                $code   = basename(str_replace('/.gitKeep', '', $path));
                $action = $system;
                $data   = ['storeCode' => $code];
            } else if (substr($path, -5) === '.json') {
                $code   = basename(str_replace('.json', '', $path));
                $action = $system;
                $data   = [
                    'referenceCode' => $code,
                    'referenceData' => Libs\Util::jsonDecode(file_get_contents($path)),
                ];
            }
        }//end if

        switch ($type) {
            case 'A':
                $this->data[] = [
                    'action' => $action.'Added',
                    'data'   => $data,
                ];
            break;

            case 'D':
                $this->data[] = [
                    'action' => $action.'Deleted',
                    'data'   => $data,
                ];
            break;

            case 'C':
            case 'M':
            case 'R':
                $this->data[] = [
                    'action' => $action.'Updated',
                    'data'   => $data,
                ];
            break;

            default:
                // Invalid type so nothing to get.
            break;
        }//end switch

    }//end gatherData()


}//end class
