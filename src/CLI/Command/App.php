<?php
/**
 * App class for Perspective Simulator CLI.
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
 * App Class
 */
class App
{
    use CommandTrait;

    /**
     * The namespace string for the custom type.
     *
     * @var string
     */
    private $baseNamespace = '';

    /**
     * The extends string for the custom type.
     *
     * @var string
     */
    private $extends = '';

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
        $projectDir          = Libs\FileSystem::getProjectDir();
        $this->storeDir      = $projectDir.'/App/';
        $this->baseNamespace = $GLOBALS['project'].'\\App';
        $this->setArgs($action, $args);

    }//end __construct()


    /**
     * Validates the app class or folder name
     *
     * @param string $name The name we are validating.
     *
     * @return string
     * @throws CLIException When name is invalid.
     */
    private function validateName(string $name)
    {
        if ($name === null) {
            $eMsg = sprintf(_('%s is required.'), $this->args['type']);
            throw new CLIException($eMsg);
        }

        $nameParts = explode(DIRECTORY_SEPARATOR, $name);
        if ($this->args['type'] === 'folder') {
            $valid = Libs\Util::isValidStringid(end($nameParts));
            if ($valid === false) {
                $eMsg = sprintf(_('Invalid %s name provided.'), $this->args['type']);
                throw new CLIException($eMsg);
            }

            if (is_dir($this->storeDir.$name) === true) {
                throw new CLIException(_('Duplicate folder name provided.'));
            }
        } else {
            $className = str_replace('.php', '', end($nameParts));
            $valid     = Libs\Util::isPHPClassString($className);
            if ($valid === false) {
                $eMsg = sprintf(_('Invalid %s name provided.'), $this->args['type']);
                throw new CLIException($eMsg);
            }

            if (file_exists($this->storeDir.$name) === true) {
                throw new CLIException(_('Duplicate class name provided.'));
            }
        }//end if

        return $name;

    }//end validateName()


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
                $this->args['type'] = ($args[0] ?? 'class');
                $this->args['name'] = ($args[1] ?? null);
            break;

            case 'move':
            case 'rename':
                $this->args['type']    = ($args[0] ?? 'class');
                $this->args['oldName'] = ($args[1] ?? null);
                $this->args['newName'] = ($args[2] ?? null);
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Adds a new App Class or Folder.
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function add()
    {
        if ($this->args['name'] === null) {
            $eMsg = sprintf('%s\'s name is required.', $this->args['type']);
            throw new CLIException($eMsg);
        }

        try {
            $this->validateName($this->args['name']);
            if ($this->args['type'] === 'folder') {
                Libs\FileSystem::mkdir($this->storeDir.$this->args['name'], true);
            } else {
                $nameParts = explode(DIRECTORY_SEPARATOR, $this->args['name']);
                array_pop($nameParts);

                if (count($nameParts) > 0) {
                    $namespace = $this->baseNamespace.'\\'.implode('\\', $nameParts);
                } else {
                    $namespace = $this->baseNamespace;
                }

                $classNameParts = explode(DIRECTORY_SEPARATOR, $this->args['name']);
                $className      = str_replace('.php', '', end($classNameParts));

                $defaultContent = Libs\Util::getDefaultPHPClass();
                $phpClass       = str_replace(
                    'CLASS_NAME',
                    $className,
                    str_replace(
                        'CLASS_EXTENDS',
                        '',
                        str_replace(
                            'NAMESPACE',
                            $namespace,
                            $defaultContent
                        )
                    )
                );

                $validCode = Libs\Util::checkPHPSyntax($phpClass);
                if ($validCode !== true) {
                    throw new CLIException($validCode);
                }

                $fileName = str_replace('.php', '', implode(DIRECTORY_SEPARATOR, $classNameParts));
                $phpFile = $this->storeDir.$fileName.'.php';

                $fileDir = $this->storeDir.implode(DIRECTORY_SEPARATOR, $nameParts);
                if (is_dir($fileDir) === false) {
                    // The directory doens't exist so we will attempt to create it too.
                    Libs\FileSystem::mkdir($fileDir, true);
                }

                file_put_contents($phpFile, $phpClass);
            }//end if
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
        if ($this->args['name'] === null) {
            $eMsg = sprintf('%s\'s name is required.', $this->args['type']);
            throw new CLIException($eMsg);
        }

        try {
            if ($this->args['type'] === 'folder') {
                $path = $this->storeDir.$this->args['name'];
                if (is_dir($path) === false) {
                    $eMsg = sprintf('Folder "%s" doesn\'t exist.', $path);
                    throw new CLIException($eMsg);
                }
            } else {
                // Remove .php incase it was provided we will readd to ensure its there.
                $path = str_replace('.php', '', $this->storeDir.$this->args['name']);
                $path = $path.'.php';

                if (file_exists($path) === false) {
                    $eMsg = sprintf('App class "%s" doesn\'t exist.', $path);
                    throw new CLIException($eMsg);
                }
            }//end if

            Libs\FileSystem::delete($path);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end delete()


    /**
     * Renames an app file or folder
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
     * Moves an app file or folder, updating this will affect $this->rename()
     *
     * @return void
     * @throws CLIException When somthing goes wrong.
     */
    public function move()
    {
        if ($this->args['oldName'] === null && $this->args['newName'] === null) {
            throw new CLIException(_('Current name and New name are required.'));
        } else if ($this->args['newName'] === null) {
            throw new CLIException(_('New name is required.'));
        }

        try {
            $this->validateName($this->args['newName']);
            if ($this->args['type'] === 'folder') {
                $oldFolder = $this->storeDir.$this->args['oldName'];
                if (is_dir($oldFolder) === false) {
                    throw new CLIException(_('Current folder doesn\'t exist.'));
                }

                $newFolder = $this->storeDir.$this->args['newName'];
                Libs\FileSystem::move($oldFolder, $newFolder);
            } else {
                $oldFile = str_replace('.php', '', $this->storeDir.$this->args['oldName']).'.php';
                if (file_exists($oldFile) === false) {
                    throw new CLIException(_('Current file doesn\'t exist.'));
                }

                $newFile = str_replace('.php', '', $this->storeDir.$this->args['newName']).'.php';

                $phpClass  = file_get_contents($oldFile);
                $nameParts = explode(DIRECTORY_SEPARATOR, $this->args['newName']);
                array_pop($nameParts);

                if (count($nameParts) > 0) {
                    $namespace = $this->baseNamespace.'\\'.implode('\\', $nameParts);
                } else {
                    $namespace = $this->baseNamespace;
                }

                $oldClassNameParts = explode(DIRECTORY_SEPARATOR, $this->args['oldName']);
                $oldClassName      = str_replace('.php', '', end($oldClassNameParts));

                $classNameParts = explode(DIRECTORY_SEPARATOR, $this->args['newName']);
                $className      = str_replace('.php', '', end($classNameParts));
                $phpClass       = Libs\Util::updatePHPCode($phpClass, ['newNamespace' => $namespace], 'namespace');
                $phpClass       = Libs\Util::updatePHPCode(
                    $phpClass,
                    [
                        'oldClassName' => $oldClassName,
                        'newClassName' => $className,
                    ],
                    'classname'
                );

                $validCode = Libs\Util::checkPHPSyntax($phpClass);
                if ($validCode !== true) {
                    throw new CLIException($validCode);
                }

                Libs\FileSystem::move($oldFile, $newFile);
                file_put_contents($newFile, $phpClass);
            }
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end rename()



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
                'action'    => sprintf('perspective [-p] add app %s', $type),
                'arguments' => [
                    'required' => [
                        'name' => _('The name of the class or folder, can also be path to file or folder location.'),
                    ],
                ],
            ],
            'delete' => [
                'action'    => sprintf('perspective [-p] delete app %s', $type),
                'arguments' => [
                    'required' => [
                        'name' => _('The name of the class or folder, can also be path to file or folder location.'),
                    ],
                ],
            ],
            'rename' => [
                'action'    => sprintf('perspective [-p] rename app %s', $type),
                'arguments' => [
                    'required' => [
                        'oldName' => _('The current name of the class or folder, can also be path to file or folder location.'),
                        'newName' => _('The new name of the class or folder, can also be path to file or folder location.'),
                    ],
                ],
            ],
            'move'   => [
                'action'    => sprintf('perspective [-p] move customtype %s', $type),
                'arguments' => [
                    'required' => [
                        'oldName' => _('The current name of the class or folder, can also be path to file or folder location.'),
                        'newName' => _('The new name of the class or folder, can also be path to file or folder location.'),
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
