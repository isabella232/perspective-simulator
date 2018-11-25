<?php
/**
 * CustomTypes Trait for Perspective Simulator CLI.
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
 * CustomTypes Trait
 */
trait CustomTypesTrait
{
    use CommandTrait;

    /**
     * Array of banned custom type names.
     *
     * @var array
     */
    private $bannedTypeNames = [
        'data',
        'page',
        'user',
        'group',
    ];

    /**
     * The namespace string for the custom type.
     *
     * @var string
     */
    private $namespace = '';

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
    private $type = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';


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
            case 'move':
                $this->args['type']   = $this->type;
                $this->args['code']   = ($args[0] ?? null);
                $this->args['parent'] = ($args[1] ?? $this->extends);
            break;

            case 'delete':
                $this->args['type'] = $this->type;
                $this->args['code'] = ($args[0] ?? null);
            break;

            case 'rename':
                $this->args['type']    = $this->type;
                $this->args['oldCode'] = ($args[0] ?? null);
                $this->args['newCode'] = ($args[1] ?? null);
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Validates the custom type code.
     *
     * @param string $code The custom type code.
     *
     * @return string
     * @throws CLIException When code is invalid.
     */
    private function validatedCustomTypeCode(string $code)
    {
        if ($code === null) {
            $eMsg = sprintf(_('%s code is required.'), $this->readableType);
            throw new CLIException($eMsg);
        }

        foreach ($this->bannedTypeNames as $banned) {
            if ($banned === strtolower($code)) {
                throw new CLIException(_('Invalid custom type name'));
            }
        }

        $valid = Libs\Util::isPHPClassString($code);

        if ($valid === false) {
            $eMsg = sprintf(_('Invalid %s code provided'), $this->readableType);
            throw new CLIException($eMsg);
        }

        $customType = $this->storeDir.$code.'.json';
        if (file_exists($customType) === true) {
            $eMsg = sprintf(_('Duplicate %s code provided'), $this->readableType);
            throw new CLIException($eMsg);
        }

        return $code;

    }//end validatedCustomTypeCode()


    /**
     * Adds a new property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function add()
    {
        if ($this->args['code'] === null) {
            $eMsg = sprintf('%s\'s code is required.', $this->readableType);
            throw new CLIException($eMsg);
        }

        try {
            $this->validatedCustomTypeCode($this->args['code']);
            if (is_dir($this->storeDir) === false) {
                Libs\FileSystem::mkdir($this->storeDir, true);
            }

            // Check parent exits.
            if ($this->args['parent'] !== $this->extends && file_exists($this->storeDir.$this->args['parent'].'.json') === false) {
                $eMsg = sprintf('%s\'s parent doesn\'t exist.', $this->readableType);
                throw new CLIException($eMsg);
            }

            // PHP file.
            $defaultContent = Libs\Util::getDefaultPHPClass();
            $phpClass       = str_replace(
                'CLASS_NAME',
                $this->args['code'],
                str_replace(
                    'CLASS_EXTENDS',
                    'extends '.$this->args['parent'],
                    str_replace(
                        'NAMESPACE',
                        $this->namespace,
                        $defaultContent
                    )
                )
            );

            $phpFile = $this->storeDir.$this->args['code'].'.php';
            file_put_contents($phpFile, $phpClass);

            // JSON file.
            $jsonData = [
                'name' => ucfirst($this->args['code']),
                'icon' => [
                    'colour' => 'red',
                    'type'   => 'template',
                ],
            ];
            $jsonFile = $this->storeDir.$this->args['code'].'.json';
            file_put_contents($jsonFile, Libs\Util::jsonEncode($jsonData));
        } catch (\Exceptions $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end add()


    /**
     * Renames a property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function rename()
    {
        if ($this->args['oldCode'] === null && $this->args['newCode'] === null) {
            throw new CLIException(_('Original and New codes are required.'));
        } else if ($this->args['newCode'] === null) {
            throw new CLIException(_('New code is required.'));
        }

        try {
            $this->validatedCustomTypeCode($this->args['newCode']);

            // JSON File update
            $oldDir = $this->storeDir.$this->args['oldCode'].'.json';
            $newDir = $this->storeDir.$this->args['newCode'].'.json';
            Libs\FileSystem::move($oldDir, $newDir);

            // PHP Class update
            $oldDir = $this->storeDir.$this->args['oldCode'].'.php';
            $newDir = $this->storeDir.$this->args['newCode'].'.php';
            Libs\FileSystem::move($oldDir, $newDir);

            $phpClass      = file_get_contents($newDir);
            $changeData    = [
                'oldClassName' => $this->args['oldCode'],
                'newClassName' => $this->args['newCode'],
            ];
            $newSourceCode = Libs\Util::updatePHPCode($phpClass, $changeData, 'classname');
            file_put_contents($newDir, $newSourceCode);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }//end try

    }//end rename()


    /**
     * Deletes a property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function delete()
    {
        $msg  = Terminal::formatText(
            _('This will delete the custom type.'),
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            $customTypeFile = $this->storeDir.$this->args['code'].'.json';
            if (file_exists($customTypeFile) === false) {
                throw new CLIException(
                    sprintf(
                        _('%1$s "%2$s" doesn\'t exist.'),
                        $this->readableType,
                        $this->args['code']
                    )
                );
            }

            Libs\FileSystem::delete($customTypeFile);
            Libs\FileSystem::delete($this->storeDir.$this->args['code'].'.php');
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end delete()


    /**
     * Deletes a property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function move()
    {
        try {
            $customTypeFile = $this->storeDir.$this->args['code'].'.php';
            if (file_exists($customTypeFile) === false) {
                throw new CLIException(
                    sprintf(
                        _('%1$s "%2$s" doesn\'t exist.'),
                        $this->readableType,
                        $this->args['code']
                    )
                );
            }

            $phpClass      = file_get_contents($customTypeFile);
            $changeData    = ['newExtends' => $this->args['parent']];
            $newSourceCode = Libs\Util::updatePHPCode($phpClass, $changeData, 'extends');
            file_put_contents($customTypeFile, $newSourceCode);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end move()


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
            'add'    => [
                'action'      => sprintf('perspective [-p] add customtype %s', $type),
                'description' => _('Adds a new Custom Type to the project'),
                'arguments'   => [
                    'required' => [
                        'customTypeCode' => _('The custom type\'s code.'),
                    ],
                    'optional' => [
                        'parent' => _('The parent type code, if not provided the default type\'s parent will be used.'),
                    ],
                ],
            ],
            'delete' => [
                'action'      => sprintf('perspective [-p] delete customtype %s', $type),
                'description' => _('Deletes a Custom Type from the project'),
                'arguments'   => [
                    'required' => [
                        'customTypeCode' => _('The custom type\'s code.'),
                    ],
                ],
            ],
            'rename' => [
                'action'      => sprintf('perspective [-p] rename customtype %s', $type),
                'description' => _('Renames a Custom Type in the project'),
                'arguments'   => [
                    'required' => [
                        'oldCustomTypeCode' => _('The current code for the custom type.'),
                        'newCustomTypeCode' => _('The new code for the custom type.'),
                    ],
                ],
            ],
            'move'   => [
                'action'      => sprintf('perspective [-p] move customtype %s', $type),
                'description' => _('Moves a Custom Type in the project'),
                'arguments'   => [
                    'required' => [
                        'customTypeCode' => _('The custom type\'s code.'),
                    ],
                    'optional' => [
                        'parent' => _('The parent type code, if not provided the default type\'s parent will be used.'),
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
                        'Usage for: perspective <action> customtype %s <arguments>',
                        $type
                    )
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
