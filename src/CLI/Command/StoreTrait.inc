<?php
/**
 * Store Trait for Perspective Simulator CLI.
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
 * Store Trait
 */
trait StoreTrait
{
    use CommandTrait;

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $type = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


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
                $this->args['storeName'] = ($args[0] ?? null);
            break;

            case 'delete':
                $this->args['storeName'] = ($args[0] ?? null);
            break;

            case 'rename':
                $this->args['oldName'] = ($args[0] ?? null);
                $this->args['newName'] = ($args[1] ?? null);
            break;

            case 'addReference':
                $this->args['targetType']    = $this->type;
                $this->args['referneceName'] = ($args[0] ?? null);
                $this->args['targetCode']    = ($args[1] ?? null);
                $this->args['sourceType']    = ($args[2] ?? null);
                $this->args['sourceCode']    = ($args[3] ?? null);
                $this->args['cardinatlity']  = ($args[4] ?? 'M:M');

                if ($this->args['referneceName'] !== null) {
                    $this->args['referneceName'] = strtolower($this->args['referneceName']);
                }

                if ($this->args['sourceType'] !== null) {
                    $tempType                 = ucfirst(substr($this->args['sourceType'], 0, -5));
                    $storeType                = ucfirst(substr($this->args['sourceType'], -5));
                    $this->args['sourceType'] = $tempType.$storeType;
                }
            break;

            case 'renameReference':
                $this->args['targetType']    = $this->type;
                $this->args['targetCode']    = ($args[0] ?? null);
                $this->args['oldName']       = ($args[1] ?? null);
                $this->args['newName']       = ($args[2] ?? null);
                $this->args['referneceName'] = $this->args['newName'];
            break;

            case 'deleteReference':
                $this->args['targetType']    = $this->type;
                $this->args['referneceName'] = ($args[0] ?? null);
                $this->args['targetCode']    = ($args[1] ?? null);
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Validates the name of the store.
     *
     * @param string $name Name of the data store.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateStoreName(string $name)
    {
        if ($name === null) {
            $eMsg = sprintf('%s name is required.', $this->readableType);
            throw new CLIException($eMsg);
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s name provided', $this->readableType);
            throw new CLIException($eMsg);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        $dirs       = glob($this->storeDir.'*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $storeName = strtolower(basename($dir));
            if (strtolower($name) === $storeName) {
                $eMsg = sprintf('%s name is already in use', $this->readableType);
                throw new CLIException($eMsg);
            }
        }

        return $name;

    }//end validateStoreName()


    /**
     * Validates the name of the reference.
     *
     * @param string $name Name of the data store.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateReferenceName(string $name)
    {
        if ($name === null) {
            throw new CLIException('Reference name is required.');
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            throw new CLIException('Invalid reference name provided');
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        $reference  = $this->storeDir.$this->args['targetCode'].'/'.$this->args['referneceName'].'.json';
        if (file_exists($reference) === true) {
            throw new CLIException('Reference name is already in use');
        }

        return $name;

    }//end validateReferenceName()


    /**
     * Adds a new store to the project.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function add()
    {
        try {
            $this->validateStoreName($this->args['storeName']);
            $dataStoreDir = $this->storeDir.$this->args['storeName'];
            Libs\FileSystem::mkdir($dataStoreDir, true);
            touch($dataStoreDir.'/.gitkeep');
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end add()


    /**
     * Deletes a store in the project.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function delete()
    {
        $msg  = Terminal::formatText(
            'This will delete the store.',
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            $dataStoreDir = $this->storeDir.$this->args['storeName'];
            if (is_dir($dataStoreDir) === false) {
                throw new CLIException(
                    sprintf(
                        '%s directory doesn\'t exist.',
                        $this->readableType
                    )
                );
            }

            Libs\FileSystem::delete($dataStoreDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end delete()


    /**
     * Renames a store in the project.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function rename()
    {
        if ($this->args['oldName'] === null && $this->args['newName'] === null) {
            throw new CLIException(
                sprintf(
                    'Original and New %s names are required.',
                    $this->readableType
                )
            );
        } else if ($this->args['newName'] === null) {
            throw new CLIException(
                sprintf(
                    'New %s name is required.',
                    $this->readableType
                )
            );
        }

        try {
            $this->validateStoreName($this->args['newName']);
            $oldDir = $this->storeDir.$this->args['oldName'];
            $newDir = $this->storeDir.$this->args['newName'];
            Libs\FileSystem::move($oldDir, $newDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end rename()


    /**
     * Adds a new refernece to a store.
     *
     * @return void
     * @throws CLIException When error occurs.
     */
    public function addReference()
    {
        if (is_dir($this->storeDir.$this->args['targetCode']) === false) {
            throw new CLIException(sprintf('%s doesn\'t exist.', $this->readableType));
        }

        $projectDir     = Libs\FileSystem::getProjectDir();
        $sourceStoreDir = $projectDir.'/Stores/';
        if ($this->args['sourceType'] === 'UserStore') {
            $sourceStoreDir .= 'User/';
        } else if ($this->args['sourceType'] === 'DataStore') {
            $sourceStoreDir .= 'Data/';
        } else {
            $sourceStoreDir = $this->storeDir;
        }

        if (is_dir($sourceStoreDir.$this->args['sourceCode']) === false) {
            throw new CLIException(sprintf('%s doesn\'t exist.', $this->args['sourceType']));
        }

        try {
            $this->validateReferenceName($this->args['referneceName']);
            $referneceDetails = [
                'sourceType'  => $this->args['sourceType'],
                'sourceCode'  => $this->args['sourceCode'],
                'targetType'  => $this->args['targetType'],
                'targetCode'  => $this->args['targetCode'],
                'cardinality' => $this->args['cardinatlity'],
            ];

            $path = $this->storeDir.$this->args['targetCode'].'/'.$this->args['referneceName'].'.json';
            file_put_contents($path, Libs\Util::jsonEncode($referneceDetails));
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end addReference()


    /**
     * Updates a refernece in a store.
     *
     * @return void
     * @throws CLIException When error occurs.
     */
    public function updateReference()
    {
        if (is_dir($this->storeDir.$this->args['targetCode']) === false) {
            throw new CLIException(sprintf('%s doesn\'t exist.', $this->readableType));
        }

        $projectDir     = Libs\FileSystem::getProjectDir();
        $sourceStoreDir = $projectDir.'/Stores/';
        if ($this->args['sourceType'] === 'UserStore') {
            $sourceStoreDir .= 'User/';
        } else if ($this->args['sourceType'] === 'DataStore') {
            $sourceStoreDir .= 'Data/';
        } else {
            $sourceStoreDir = $this->storeDir;
        }

        if (is_dir($sourceStoreDir.$this->args['sourceCode']) === false) {
            throw new CLIException(sprintf('%s doesn\'t exist.', $this->args['sourceType']));
        }

        try {
            $referneceDetails = [
                'sourceType'  => $this->args['sourceType'],
                'sourceCode'  => $this->args['sourceCode'],
                'targetType'  => $this->args['targetType'],
                'targetCode'  => $this->args['targetCode'],
                'cardinality' => $this->args['cardinatlity'],
            ];

            $path = $this->storeDir.$this->args['targetCode'].'/'.$this->args['referneceName'].'.json';
            if (file_exists($path) === false) {
                throw new CLIException(
                    sprintf(
                        '%s doesn\'t exist.',
                        $this->args['referneceName']
                    )
                );
            }

            file_put_contents($path, Libs\Util::jsonEncode($referneceDetails));
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end updateReference()


    /**
     * Deletes a refernec in the store.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function deleteReference()
    {
        $msg  = Terminal::formatText(
            'This will delete the reference.',
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            $ref = $this->storeDir.$this->args['targetCode'].'/'.$this->args['referneceName'].'.json';
            if (file_exists($ref) === false) {
                throw new CLIException(
                    sprintf(
                        '%s doesn\'t exist.',
                        $this->args['referneceName']
                    )
                );
            }

            Libs\FileSystem::delete($ref);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end deleteReference()


    /**
     * Renames a reference in a store
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function renameReference()
    {
        if ($this->args['oldName'] === null && $this->args['newName'] === null) {
            throw new CLIException('Original and New names are required.');
        } else if ($this->args['newName'] === null) {
            throw new CLIException('New name is required.');
        }

        try {
            $this->validateReferenceName($this->args['newName']);
            $oldDir = $this->storeDir.$this->args['targetCode'].'/'.$this->args['oldName'].'.json';
            $newDir = $this->storeDir.$this->args['targetCode'].'/'.$this->args['newName'].'.json';
            Libs\FileSystem::move($oldDir, $newDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end renameReference()


    /**
     * Prints the help to the terminal for store commands.
     *
     * @param string $filter Action to filter by.
     *
     * @return void
     */
    final public function printHelp(string $filter=null)
    {
        $type    = strtolower($this->type);
        $actions = [
            'add'             => [
                'action'      => sprintf('perspective [-p] add %s', $type),
                'description' => 'Adds a new store for use in the project',
                'arguments'   => [
                    'required' => [
                        'storeName' => 'The name of the new store to be created.',
                    ],
                ],
            ],
            'delete'          => [
                'action'      => sprintf('perspective [-p] delete %s', $type),
                'description' => 'Deletes a store from the project',
                'arguments'   => [
                    'required' => [
                        'storeName' => 'The name of the store to be deleted.',
                    ],
                ],
            ],
            'rename'          => [
                'action'      => sprintf('perspective [-p] rename %s', $type),
                'description' => 'Renames a store in the project',
                'arguments'   => [
                    'required' => [
                        'oldName' => 'The current name of the store.',
                        'newName' => 'The new name of the store.',
                    ],
                ],
            ],
            'addReference'    => [
                'action'      => sprintf('perspective [-p] addReference %s', $type),
                'description' => 'Adds a reference between two stores',
                'arguments'   => [
                    'required' => [
                        'referneceName' => 'The name of the reference we are adding.',
                        'targetCode'    => 'The name of the store the reference will be added to.',
                        'sourceType'    => 'The type of store being referenced.',
                        'sourceCode'    => 'The name of the store being referenced.',
                    ],
                    'optional' => [
                        'cardinatlity' => 'The cardinatlity of the reference, eg. 1:1, 1:M or M:M, if not provided M:M will be used.',
                    ],
                ],
            ],
            'renameReference' => [
                'action'      => sprintf('perspective [-p] renameReference %s', $type),
                'description' => 'Renames the reference between two stores',
                'arguments'   => [
                    'required' => [
                        'targetCode' => 'The name of the store the reference belongs to.',
                        'oldName'    => 'The current name of the reference.',
                        'newName'    => 'The new name for the reference.',
                    ],
                ],
            ],
            'deleteReference' => [
                'action'      => sprintf('perspective [-p] deleteReference %s', $type),
                'description' => 'Deletes the reference between two stores',
                'arguments'   => [
                    'required' => [
                        'referneceName' => 'The name of the reference we are deleting.',
                        'targetCode'    => 'The name of the store the reference will be deleted from.',
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
                    sprintf(
                        'Usage for: perspective <action> %s <arguments>',
                        $type
                    )
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
