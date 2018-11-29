<?php
/**
 * Job class.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Queue;

/**
 * Job Class.
 */
class Job
{

    /**
     * The user supplied data for the job.
     *
     * @var mixed
     */
    private $data = null;

    /**
     * The number of times this job has been attempted.
     *
     * @var integer
     */
    private $retryCount = 0;

    /**
     * The last status of this job.
     *
     * @var string
     */
    private $status = 'unhandled';

    /**
     * The number of seconds the job will be delayed for in the exchange before it is retried.
     *
     * @var integer
     */
    private $retryDelaySeconds = 0;


    /**
     * Class Constructor.
     *
     * @param mixed   $data       The user data provided for the job.
     * @param integer $retryCount The number of times the job has been retried.
     *
     * @return void
     */
    final public function __construct($data, int $retryCount)
    {
        $this->setData($data);
        $this->retryCount = $retryCount;

    }//end __construct()


    /**
     * Returns the user supplied job data.
     *
     * @return mixed
     */
    final public function getData()
    {
        return $this->data;

    }//end getData()


    /**
     * Call to set the user supplied data.
     *
     * @param mixed $data The data to set.
     *
     * @return void
     */
    final public function setData($data)
    {
        $this->data = $data;

    }//end setData()


    /**
     * Returns the number of times this job has been attempted.
     *
     * @return integer
     */
    final public function getRetryCount()
    {
        return $this->retryCount;

    }//end getRetryCount()


    /**
     * Call to request this job retry with the latest data set at the end of the workers execution.
     *
     * @param integer $retryDelaySeconds The number of seconds before we retry.
     *
     * @return void
     * @throws \Exception When delay seconds is given as a negative integer.
     */
    final public function retry(int $retryDelaySeconds=0)
    {
        if ($retryDelaySeconds < 0) {
            throw new \Exception('The number of seconds to delay must be a positive integer or zero.');
        }

        $this->status            = 'retry';
        $this->retryDelaySeconds = $retryDelaySeconds;

    }//end retry()


    /**
     * Returns the number of seconds the job will be delayed for in the exchange before it is retried.
     *
     * @return integer
     */
    final public function getRetryDelaySeconds()
    {
        return $this->retryDelaySeconds;

    }//end getRetryDelaySeconds()


    /**
     * Call to cancel this job at the end of the workers execution.
     *
     * Failure to mark a job as retry, cancel or complete will result in an unhandled requeuing with latest data.
     *
     * @return void
     */
    final public function cancel()
    {
        $this->status = 'cancel';

    }//end cancel()


    /**
     * Call to mark this job as complete which is read at the end of the workers execution.
     *
     * Failure to mark a job as retry, cancel or complete will result in an unhandled requeuing with latest data.
     *
     * @return void
     */
    final public function complete()
    {
        $this->status = 'complete';

    }//end complete()


    /**
     * Returns the current status.
     *
     * @return string
     */
    final public function getStatus()
    {
        return $this->status;

    }//end getStatus()


}//end class
