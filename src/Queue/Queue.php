<?php
/**
 * Queue class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Queue;

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;

/**
 * Queue Class
 */
class Queue
{

    /**
     * Array of queue jobs.
     *
     * @var array
     */
    private static $queue = [];


    /**
     * Loads the session.
     *
     * @return boolean
     */
    public static function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $filePath = Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/queue.json';
        if (file_exists($filePath) === false) {
            return false;
        }

        self::$queue = Libs\Util::jsonDecode(file_get_contents($filePath));
        return true;

    }//end load()


    /**
     * Saves the session.
     *
     * @return boolean
     */
    public static function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $filePath = Libs\FileSystem::getSimulatorDir().'/'.$GLOBALS['project'].'/queue.json';
        file_put_contents($filePath, Libs\Util::jsonEncode(self::$queue));

        return true;

    }//end save()


    /**
     * Gets the Queue file path.
     *
     * @param string $project The namespace of the project.
     *
     * @return string
     */
    public static function getQueuePath(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBASL['project'];
        }

        return \PerspectiveSimulator\Libs\FileSystem::getProjectDir($project).'/Queues';

    }//end getQueuePath()


    /**
     * Validate the queue names.
     *
     * @param array $queueNames The queue name(s) to queue this job up with.
     *
     * @return boolean
     */
    private static function validateQueues(array $queueNames)
    {
        $valid = true;

        foreach ($queueNames as $queueName) {
            if (method_exists('\\'.$GLOBALS['project'].'\\JobQueue', $queueName) === false) {
                $valid = false;
            }
        }

        return $valid;

    }//end validateQueues()


    /**
     * Adds queue job for simulation.
     *
     * @param array    $queueNames      The queue name(s) to queue this job up with.
     * @param mixed    $jobData         The data for the job that is being queued.
     * @param callable $successCallback An optional callback we will call on successful creation of the job.
     * @param callable $failedCallback  An optional callback we will call on failure to create the job.
     *
     * @return void
     */
    public static function addJob(
        array $queueNames,
        $jobData,
        callable $successCallback=null,
        callable $failedCallback=null
    ) {
        $valid = self::validateQueues($queueNames);
        if ($valid === false) {
            if ($failedCallback !== null) {
                call_user_func($failedCallback);
            }

            return;
        }

        $name    = implode('.', $queueNames);
        $jobData = [
            'userSuppliedData'      => $jobData,
            'subProjectid'          => null,
            'retryCount'            => 0,
            'unhandledFailureCount' => 0,
        ];

        if (isset(self::$queue[$name]) === false) {
            self::$queue[$name] = [];
        }

        self::$queue[$name][] = [
            'jobData'               => $jobData,
            'createSuccessCallback' => $successCallback,
            'createFailedCallback'  => $failedCallback,
            'delaySeconds'          => 0,
        ];

        self::save();

        if ($successCallback !== null) {
            call_user_func($successCallback);
        }

    }//end addJob()


    /**
     * Process the queue for the simulator.
     *
     * @param string $project    The project we are in.
     * @param array  $queueNames The names of the ques we want to process.
     *
     * @return array
     */
    public static function processQueue(string $project, array $queueNames=[])
    {
        if (empty(self::$queue) === true && self::load() === false) {
            return;
        }

        $results = [];
        if (empty($queueNames) === true) {
            foreach (self::$queue as $queueNameStr => $sameTopicQueues) {
                foreach ($sameTopicQueues as $jobData) {
                    $results[] = self::processJob($project, $queueNameStr, $jobData['jobData']['userSuppliedData']);
                }

                unset(self::$queue[$queueNameStr]);
            }
        } else {
            foreach ($queueNames as $queueNameStr) {
                if (is_array($queueNameStr) === true) {
                    $queueNameStr = implode('.', $queueNameStr);
                }

                if (isset(self::$queue[$queueNameStr]) === true) {
                    $userSuppliedData = null;

                    foreach (self::$queue[$queueNameStr] as $jobData) {
                        $results[] = self::processJob($project, $queueNameStr, $jobData['jobData']['userSuppliedData']);
                    }

                    unset(self::$queue[$queueNameStr]);
                }
            }
        }

        self::save();

        return $results;

    }//end processQueue()


    /**
     * Processes the actual queue job.
     *
     * @param string $project   The project we are in.
     * @param string $queueName The name of the queue we are processing.
     * @param mixed  $data      Array of job data.
     *
     * @return void
     */
    private static function processJob(string $project, string $queueName, $data)
    {
        $results        = [];
        $className      = '\\'.$project.'\\JobQueue';
        $queueNameParts = explode('.', $queueName);
        foreach ($queueNameParts as $name) {
            if (method_exists($className, $name) === false) {
                // Log to the error_log instead of throwing an \Exception so we can continue to run other jobs.
                // phpcs:disable
                error_log(sprintf('Queue "%s" does not exist in project \'%s\'.', $name, $project));
                // phpcs:enable

                continue;
            }

            $job       = new Job($data, 0);
            $results[] = $className::$name($job);
        }

        return $results;

    }//end processJob()


    /**
     * Gets the job function code.
     *
     * @param string $project   The project we are in.
     * @param string $queueName The name of the queue job we are running.
     *
     * @return string
     * @throws \Exception When the queue job doesn't exist.
     */
    public static function getJob(string $project, string $queueName)
    {
        $file = self::getQueuePath($project).'/'.$queueName.'.php';
        if (is_file($file) === false) {
            throw new \Exception('Queue job "'.$queueName.'" does not exist');
        }

        $content = file_get_contents($file);
        $content = str_replace('<?php', '', $content);
        return $content;

    }//end getJob()


    /**
     * Bakes queue functions.
     *
     * @param string $project The project we are using.
     *
     * @return boolean
     * @throws \Exception When unable to get Queues.
     */
    public static function installQueues(string $project)
    {
        $queuePath = self::getQueuePath($project);
        if (is_dir($queuePath) === true) {
            $queueClass  = Libs\Util::printCode(0, '<?php');
            $queueClass .= Libs\Util::printCode(0, 'namespace '.$project.';');
            $queueClass .= Libs\Util::printCode(0, '');
            $queueClass .= Libs\Util::printCode(0, 'class JobQueue');
            $queueClass .= Libs\Util::printCode(0, '{');
            $queueClass .= Libs\Util::printCode(0, '');
            $queueClass .= Libs\Util::printCode(0, '');

            $files = scandir($queuePath);

            foreach ($files as $file) {
                if ($file[0] === '.'
                    || substr($file, -4) !== '.php'
                ) {
                    continue;
                }

                $queueName   = substr($file, 0, -4);
                $queueClass .= Libs\Util::printCode(1, 'public static function '.$queueName.'(&$job)');
                $queueClass .= Libs\Util::printCode(1, '{');
                $queueClass .= Libs\Util::printCode(
                    2,
                    '$content = \PerspectiveSimulator\Queue\Queue::getJob(__NAMESPACE__, \''.$queueName.'\');'
                );
                $queueClass .= Libs\Util::printCode(2, 'return eval($content);');
                $queueClass .= Libs\Util::printCode(1, '}');
                $queueClass .= Libs\Util::printCode(0, '');
                $queueClass .= Libs\Util::printCode(0, '');
            }

            $queueClass .= Libs\Util::printCode(0, '}');

            $queueFile = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/'.$project.'/JobQueue.php';
            file_put_contents($queueFile, $queueClass);
        }//end if

        return true;

    }//end installQueues()


}//end class
