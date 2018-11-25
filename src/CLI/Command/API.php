<?php
/**
 * API class for Perspective Simulator CLI.
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
 * API Class
 */
class API
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
        $this->storeDir = $projectDir.'/API/';
        $this->setArgs($action, $args);

    }//end __construct()


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
                $this->args['path'] = ($args[0] ?? null);
            break;

            case 'update':
                $this->args['path'] = ($args[0] ?? null);
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Adds a new specification file, changes to this will affect update when path is given.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function add()
    {
        if ($this->args['path'] === null) {
            throw new CLIException(_('Path to new API specification file is required.'));
        }

        try {
            if (Libs\FileSystem::getExtension($this->args['path']) !== 'yaml') {
                throw new CLIException(_('Only yaml API specification files are supported.'));
            }

            copy($this->args['path'], $this->storeDir.'api.yaml');
            \PerspectiveSimulator\API::installAPI($GLOBALS['project']);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end add()


    /**
     * Deletes a App Class or Folder.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function delete()
    {
        $msg  = Terminal::formatText(
            _('This will remove the API specification and all its operations from the project.'),
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            Libs\FileSystem::delete($this->storeDir.'api.yaml');
            Libs\FileSystem::delete($this->storeDir.'/Operations/');
            $simDir = Libs\FileSystem::getSimulatorDir();
            Libs\FileSystem::delete($simDir.$GLOBALS['project'].'/API.php');
            Libs\FileSystem::delete($simDir.$GLOBALS['project'].'/APIRouter.php');
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end delete()


    /**
     * This either replaces the API specification file or reinstalls the API function stubs and router.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function update()
    {
        try {
            if ($this->args['path'] !== null) {
                $this->add();
            } else {
                \PerspectiveSimulator\API::installAPI($GLOBALS['project']);
            }
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
                'action'      => 'perspective [-p] add api',
                'description' => _('Adds a new API specification file.'),
                'arguments'   => [
                    'optional' => [
                        'path' => _('The path to a new API specification yaml file.'),
                    ],
                ],
            ],
            'delete' => [
                'action'      => 'perspective [-p] delete api',
                'description' => _('Deletes the projects API specification file.'),
                'arguments'   => [],
            ],
            'update' => [
                'action'      => 'perspective [-p] rename api',
                'description' => _('Updates the projects API specification file and its operations.'),
                'arguments'   => [
                    'optional' => [
                        'path' => _('The path to a new API specification yaml file.'),
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
        }

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
