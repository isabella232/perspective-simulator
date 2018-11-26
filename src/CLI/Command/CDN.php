<?php
/**
 * CDN class for Perspective Simulator CLI.
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
 * CDN Class
 */
class CDN
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
        $this->storeDir = $projectDir.'/CDN/';
        $this->setArgs($action, $args);

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

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
            case 'delete':
                $this->args['type']    = ($args[0] ?? 'directory');
                $this->args['cpPath']  = ($args[1] ?? null);
                $this->args['cdnPath'] = ($args[2] ?? null);
            break;

            case 'move':
            case 'rename':
                $this->args['type']    = ($args[0] ?? 'directory');
                $this->args['oldPath'] = ($args[1] ?? null);
                $this->args['newPath'] = ($args[2] ?? null);
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
        if ($this->args['cpPath'] === null) {
            throw new CLIException(_('Path to new API specification file is required.'));
        }

        try {
            if ($this->args['type'] === 'directory') {
                Libs\FileSystem::mkdir($this->storeDir.$this->args['cpPath'], true);
            } else {
                copy($this->args['cpPath'], $this->storeDir.$this->args['cdnPath']);
            }
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
        $msg = Terminal::formatText(
            sprintf(
                _('This will remove %s from the projects\' CDN.'),
                $this->args['cpPath']
            ),
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            if ($this->args['type'] === 'directory') {
                if (is_dir($this->storeDir.$this->args['cpPath']) === false) {
                    throw new CLIException(_('Invalid CDN directory.'));
                }
            } else {
                if (file_exists($this->storeDir.$this->args['cpPath']) === false) {
                    throw new CLIException(_('CDN file doens\'t exist.'));
                }
            }

            Libs\FileSystem::delete($this->storeDir.$this->args['cpPath']);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end delete()


    /**
     * Renames an app file or directory
     * This just wraps move for ease of commands.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function rename()
    {
        try {
            $this->move();
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end rename()


    /**
     * Moves an app file or directory, updating this will affect $this->rename()
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function move()
    {
        if ($this->args['oldPath'] === null && $this->args['newPath'] === null) {
            throw new CLIException(_('Current path and New path are required.'));
        } else if ($this->args['newPath'] === null) {
            throw new CLIException(_('New path is required.'));
        }

        try {
            if ($this->args['type'] === 'directory') {
                if (is_dir($this->storeDir.$this->args['oldPath']) === false) {
                    throw new CLIException(_('The old CDN path doesn\'t exist.'));
                } else if (is_dir($this->storeDir.$this->args['newPath']) === true) {
                    throw new CLIException(_('CDN directory already exits.'));
                }
            } else {
                if (file_exists($this->storeDir.$this->args['oldPath']) === false) {
                    throw new CLIException(_('The old CDN file doesn\'t exist.'));
                } else if (file_exists($this->storeDir.$this->args['newPath']) === true) {
                    throw new CLIException(_('CDN file already exits.'));
                }
            }//end if

            Libs\FileSystem::move($this->storeDir.$this->args['oldPath'], $this->storeDir.$this->args['newPath']);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end move()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    public function printHelp(string $filter=null)
    {
        $type    = strtolower(($this->args['type'] ?? 'class/directory'));
        $actions = [
            'add'    => [
                'action'      => sprintf('perspective [-p] add cdn %s', $type),
                'description' => _('Copies a file to the CDN for the project or makes a new directory.'),
                'arguments'   => [
                    'required' => [
                        'path'         => _('The absolute path to the file to copy or the CDN path for a directory.'),
                        'locationPath' => _('The CDN path to copy the file to, OPTIONAL when adding a directory.'),
                    ],
                ],
            ],
            'delete' => [
                'action'      => sprintf('perspective [-p] delete cdn %s', $type),
                'description' => _('Deletes the file or directory from the CDN in project.'),
                'arguments'   => [
                    'required' => [
                        'path' => _('The path to the file or directory to delete from the CDN'),
                    ],
                ],
            ],
            'rename' => [
                'action'      => sprintf('perspective [-p] move cdn %s', $type),
                'description' => _('Moves a file or directory in the CDN of the project.'),
                'arguments'   => [
                    'required' => [
                        'oldPath' => _('The current CDN path for the file or directory.'),
                        'newPath' => _('The new CDN path for the file or directory.'),
                    ],
                ],
            ],
            'move'   => [
                'action'      => sprintf('perspective [-p] rename cdn %s', $type),
                'description' => _('Renames a file or directory in the CDN.'),
                'arguments'   => [
                    'required' => [
                        'oldPath' => _('The current CDN path for the file or directory.'),
                        'newPath' => _('The new CDN path for the file or directory.'),
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
                    'Usage for: perspective <action> cdn <arguments>'
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
