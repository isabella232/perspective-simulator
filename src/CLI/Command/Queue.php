<?php
/**
 * Queue class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

require_once dirname(__FILE__).'/CommandTrait.inc';

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\CLI\Terminal;
use \PerspectiveSimulator\Exceptions\CLIException;

/**
 * Queue Class
 */
class Queue
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
        $projectDir     = Libs\FileSystem::getProjectDir();
        $this->storeDir = $projectDir.'/Queues/';
        $this->setArgs($action, $args);

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

    }//end __construct()


    /**
     * Validates a queue name.
     *
     * @param string $name The name to validate.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateQueueName(string $name)
    {
        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            throw new CLIException(_('Queue name invalid.'));
        }

        $queueFile = $this->storeDir.$name.'.php';
        if (file_exists($queueFile) === true) {
            throw new CLIException(_('Duplicate queue name.'));
        }

        return $name;

    }//end validateQueueName()

    /**
     * Rebakes the queue functions.
     *
     * @return void
     */
    private function rebake()
    {
        \PerspectiveSimulator\Queue\Queue::installQueues($GLOBALS['project']);

    }//end rebake()


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
            case 'delete':
                $this->args['name'] = ($args[0] ?? null);
            break;

            case 'rename':
                $this->args['oldName'] = ($args[0] ?? null);
                $this->args['newName'] = ($args[1] ?? null);
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Adds a new queue to a project.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function add()
    {
        if ($this->args['name'] === null) {
            throw new CLIException(_('Queue name is required.'));
        }

        try {
            $this->validateQueueName($this->args['name']);
            $defaultQueue = '
/**
 * Queue function for QUEUE_NAME.
 *
 * @param object $job The job object passed.
 */

';

            $queueCode = str_replace(
                'QUEUE_NAME',
                $this->args['name'],
                $defaultQueue
            );

            $queueFile = $this->storeDir.$this->args['name'].'.php';
            file_put_contents($queueFile, $queueCode);

            $this->rebake();
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end add()


    /**
     * Deletes a queue from the project.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function delete()
    {
        if ($this->args['name'] === null) {
            throw new CLIException(_('Queue name is required.'));
        }

        $msg  = Terminal::formatText(
            sprintf(_('This will delete %s queue from the project'), $this->args['name']),
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            Libs\FileSystem::delete($this->storeDir.$this->args['name'].'.php');
            $this->rebake();
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end delete()


    /**
     * Renames a queue.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function rename()
    {
        if ($this->args['oldName'] === null && $this->args['newName'] === null) {
            throw new CLIException(_('Queue\'s current name and new name are required.'));
        } else if ($this->args['newName'] === null) {
            throw new CLIException(_('Queue\'s new name is required.'));
        }

        try {
            if (file_exists($this->storeDir.$this->args['oldName'].'.php') === false) {
                throw new CLIException(_('Queue doesn\'t exist.'));
            }

            $this->validateQueueName($this->args['newName']);
            Libs\FileSystem::move(
                $this->storeDir.$this->args['oldName'].'.php',
                $this->storeDir.$this->args['newName'].'.php'
            );
            $this->rebake();
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

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
        $type    = strtolower(($this->args['type'] ?? 'class/folder'));
        $actions = [
            'add'    => [
                'action'      => 'perspective [-p] add queue',
                'description' => _('Adds a new Queue to a project.'),
                'arguments'   => [
                    'required' => [
                        'queueName' => _('The name for the new queue.'),
                    ],
                ],
            ],
            'delete' => [
                'action'      => 'perspective [-p] delete queue',
                'description' => _('Deletes a queue in a project.'),
                'arguments'   => [
                    'required' => [
                        'queueName' => _('The name for the queue being deleted.'),
                    ],
                ],
            ],
            'rename' => [
                'action'      => 'perspective [-p] rename queue',
                'description' => _('Renames a queue.'),
                'arguments'   => [
                    'optional' => [
                        'oldName' => _('The current name of the queue.'),
                        'newName' => _('The new name for the queue.'),
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
                    'Usage for: perspective <action> api <arguments>'
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
