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
     * @param array  $args   [description]
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

            default:
                $this->args = $args;
            break;
        }

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
            $eMsg = sprintf(_('%s name is required.'), $this->readableType);
            throw new CLIException($eMsg);
        }

        $valid      = Libs\Util::isValidStringid($name);
        $projectDir = Libs\FileSystem::getProjectDir();
        $dirs       = glob($this->storeDir.'*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $storeName = strtolower(basename($dir));
            if (strtolower($name) === $storeName) {
                $eMsg = sprintf(_('%s name is already in use'), $this->readableType);
                throw new CLIException($eMsg);
            }
        }

        if ($valid === false) {
            $eMsg = sprintf(_('Invalid %s name provided'), $this->readableType);
            throw new CLIException($eMsg);
        }

        return $name;

    }//end validateStoreName()


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
            $dataStoreDir  = Libs\FileSystem::getProjectDir().'/Stores/Data/'.$this->args['storeName'];
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
        try {
            $this->validateStoreName($this->args['storeName']);
            $dataStoreDir = Libs\FileSystem::getProjectDir().'/Stores/Data/'.$this->args['storeName'];
            if (is_dir($dataStoreDir) === false) {
                throw new CLIException(
                    sprintf(
                        _('%s directory doesn\'t exist.'),
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
    public function rename(array $args=[])
    {
        if ($this->args['oldName'] == null && $this->args['newName'] === null) {
            throw new CLIException(
                sprintf(
                    _('Original and New %s names are required.'),
                    $this->readableType
                )
            );
        } else if ($this->args['newName'] == null) {
            throw new CLIException(
                sprintf(
                    _('New %s name is required.'),
                    $this->readableType
                )
            );
        }

        try {
            $this->validateStoreName($this->args['newName']);
            $oldDir        = $this->storeDir.$this->args['oldName'];
            $newDir        = $this->storeDir.$this->args['newName'];
            Libs\FileSystem::move($oldDir, $newDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end rename()


}//end class
