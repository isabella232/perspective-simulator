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
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * DeployCommand Class
 */
class DeployCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
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
     * The progress bar object
     *
     * @var object
     */
    private $progressBar = null;

    /**
     * The progress of the file being sent.
     *
     * @var integer
     */
    private $progress = 0;

    /**
     * The size of the file being sent.
     *
     * @var integer
     */
    private $size = 0;

    /**
     * The version we are depolying.
     *
     * @var string
     */
    private $version = '';

    /**
     * The checksum of the file we are sending.
     *
     * @var string
     */
    private $checksum = '';

    /**
     * The project we are trying to deploy.
     *
     * @var string
     */
    private $project = '';

    /**
     * The deployment Id for when we are sending chunked data across.
     *
     * @var null
     */
    private $deploymentid = null;

    /**
     * The gateway receipt for the deploymeny progress.
     *
     * @var integer
     */
    private $receipt = 0;

    /**
     * Number of seconds between checking the status.
     *
     * @var integer
     */
    private $checkDelay = 1;

    /**
     * Flag for initial export.
     *
     * @var boolean
     */
    private $initial = false;


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Deploys the project.');
        $this->setHelp('Deploys a given project.');
        $this->addOption(
            'initial',
            'i',
            InputOption::VALUE_NONE,
            'Flag for when the deployment is the initial.',
            null
        );
        $this->addArgument('oldVersion', InputArgument::OPTIONAL, 'The previous version number or commit ID.');
        $this->addArgument('newVersion', InputArgument::OPTIONAL, 'The new version number eg: 0.0.1');

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
            // Clean up any old deployment data.
            Libs\FileSystem::delete($this->dataDir);
            Libs\FileSystem::mkdir($this->dataDir, true);
        }

        $this->initial = $input->getOption('initial');

        if ($this->initial === true) {
            // Get the initial commit hash and use that for the oldVersion.
            exec('git rev-list --max-parents=0 HEAD', $out, $retval);
            if (empty($out) === true) {
                throw new \Exception('Unable to determine the initial version.');
            }

            $input->setArgument('newVersion', $input->getArgument('oldVersion'));
            $input->setArgument('oldVersion', $out[0]);
        }

        $newVersion = ($input->getArgument('newVersion') ?? null);
        $helper     = $this->getHelper('question');

        if ($newVersion === null) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter a new version number: ');
            $newVersion = $helper->ask($input, $output, $question);
            $input->setArgument('newVersion', $newVersion);
        }

        $re      = '/^\d+(\.\d+)*$/';
        $matches = [];
        preg_match($re, $newVersion, $matches);
        if (empty($matches) === true) {
            $this->style->error('Invalid version number. Version number can only contain . or intergers.');
            exit(1);
        }

        // Run predepolyment check command.
        $preFlightCommand = $this->getApplication()->find('deployment:preflightchecks');
        $preFlightArgs    = [
            'command'   => 'deployment:preflightchecks',
            '--project' => ($input->getOption('project') ?? ''),
        ];

        $preFlightInput = new \Symfony\Component\Console\Input\ArrayInput($preFlightArgs);
        $returnCode     = $preFlightCommand->run($preFlightInput, $output);

        // Run the diff command so the user will know what changes are about to be made.
        $diffCommand = $this->getApplication()->find('deployment:diff');
        $diffArgs    = [
            'command'   => 'deployment:diff',
            '--project' => ($input->getOption('project') ?? ''),
            'from'      => $input->getArgument('oldVersion'),
        ];

        $diffInput  = new \Symfony\Component\Console\Input\ArrayInput($diffArgs);
        $returnCode = $diffCommand->run($diffInput, $output);

        $helper  = $this->getHelper('question');
        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'The above changes have been detected. Do you want to continue the deployment? (y/N)',
            false
        );

        $continue = $helper->ask($input, $output, $confirm);
        if ($continue === false) {
            exit(1);
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
        $version = $input->getArgument('newVersion');
        $project = str_replace('\\', '/', $input->getOption('project'));

        $this->version = $version;
        $this->project = $input->getOption('project');

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

        if (file_exists(Libs\FileSystem::getExportDir().'/'.str_replace('/', '-', $project).'-instructions.json') === true) {
            copy(Libs\FileSystem::getExportDir().'/'.str_replace('/', '-', $project).'-instructions.json', $tarDir.'/'.$project.'/instructions.json');
        }

        exec('cp -r '.$projectSrcDir.' '.$tarDir.'/'.$project.'/src/');
        exec('cp -r '.$projectVenDir.' '.$tarDir.'/'.$project.'/vendor/');

        exec($tarCommand, $tarOutput, $tarRC);

        if ($tarRC !== 0) {
            $this->progressBar->finish();
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

        if ($this->send($project, $versionFile) === false) {
            $this->progressBar->finish();
            throw new \Exception('Unable to depoly project to gateway.');
        }

        // Cleanup the deploy files as the depolyment was successful.
        Libs\FileSystem::delete($versionFile);
        Libs\FileSystem::delete($tarDir.'/'.$project);

        // Only get the progress if receipt is received.
        if ($this->receipt !== null) {
            $maxSteps = ($maxSteps + 6);
            $this->progressBar->setMaxSteps($maxSteps);
            $this->progressBar->setMessage('<comment>Waiting</comment>', 'titleMessage');
            $this->progressBar->advance(0);
            $status  = null;
            $headers = [
                'Content-type: application/x-www-form-urlencoded',
                'Authentication: Basic '.$this->gateway->getGatewayKey(),
            ];
            $url     = $this->gateway->getGatewayURL().'/deployment/progress/'.$this->receipt;
            $options = [
                'http' => [
                    'header'  => $headers,
                    'method'  => 'GET',
                    'content' => '',
                ],
            ];

            $context    = stream_context_create($options);
            $prevStatus = null;
            while ($status !== 'Complete' && strpos($status, 'Error') !== 0) {
                $result     = Libs\Util::jsonDecode(file_get_contents($url, false, $context));
                $prevStatus = $status;
                $status     = ($result['status'] ?? 'Error: status not returned.');
                if (strpos($status, 'Error') !== 0) {
                    $this->progressBar->setMessage('<comment>'.$status.'</comment>', 'titleMessage');
                    if ($prevStatus !== $status) {
                        $this->progressBar->advance();
                    }

                    if ($status !== 'Complete') {
                        // Wait before each retry, this might want to be higher.
                        sleep($this->checkDelay);
                    }
                } else {
                    // Throw error.
                    throw new \Exception($status);
                }
            }

            if (strpos($status, 'Error') === 0) {
                throw new \Exception($status);
            }
        }//end if

        $this->progressBar->setMessage('', 'titleMessage');
        $this->progressBar->finish();

    }//end execute()


    /**
     * Sends the depolyment file in chunks to Gateway.
     *
     * @param string $project The projet we are depolying.
     * @param string $file    The file path of the file we are sending.
     *
     * @return mixed
     */
    private function send(string $project, string $file)
    {
        $this->checksum = sha1_file($file);
        $this->size     = filesize($file);
        $success        = false;
        $chunkByteSize  = (8 * 1024 * 1024);
        $handle         = fopen($file, 'rb');
        $getChunk       = function () use ($handle, $chunkByteSize) {
            $byteCount  = 0;
            $giantChunk = '';
            while (feof($handle) === false) {
                $chunk       = fread($handle, 8192);
                $byteCount  += strlen($chunk);
                $giantChunk .= $chunk;
                if ($byteCount >= $chunkByteSize) {
                    return $giantChunk;
                }
            }

            return $giantChunk;
        };

        while ($success === false && feof($handle) === false) {
            $chunk   = $getChunk();
            $success = $this->sendChunk($chunk);
        }

        fclose($handle);
        return $success;

    }//end send()


    /**
     * Sends the chunk to gateway.
     *
     * @param mixed $chunk The chunk we are sending.
     *
     * @return boolean
     */
    private function sendChunk($chunk)
    {
        if ($chunk === false) {
            return true;
        }

        $lastBytePos    = ($this->progress + strlen($chunk) - 1);
        $headers        = [
            'Content-range: bytes '.$this->progress.'-'.$lastBytePos.'/'.$this->size,
            'Content-type: application/x-www-form-urlencoded',
            'Authentication: Basic '.$this->gateway->getGatewayKey(),
        ];
        $this->progress = ($lastBytePos + 1);

        $sendData = [
            'data'         => $chunk,
            'checksum'     => $this->checksum,
            'deploymentid' => $this->deploymentid,
        ];

        $url     = $this->gateway->getGatewayURL().'/deployment/'.str_replace('\\', '/', $this->project).'/'.$this->version;
        $options = [
            'http' => [
                'header'        => $headers,
                'method'        => 'POST',
                'content'       => http_build_query($sendData),
                'ignore_errors' => true,
            ],
        ];

        $context    = stream_context_create($options);
        $result     = Libs\Util::jsonDecode(file_get_contents($url, false, $context));
        $statusLine = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $status = $match[1];
        if ($status !== '200') {
            throw new \Exception($statusLine."\n".$result);
        }

        $this->deploymentid = $result['versionid'];
        $this->receipt      = $result['receipt'];
        if ($result === false || ($this->progress !== $this->size)) {
            return false;
        }

        return true;

    }//end sendChunk()


}//end class
