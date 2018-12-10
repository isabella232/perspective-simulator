<?php
/**
 * Property Trait for Perspective Simulator CLI.
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
 * Property Trait
 */
trait PropertyTrait
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
    private $storeDir = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';

    /**
     * Array of initial settings for each property type.
     *
     * @var array
     */
    private $defaultSettings = [
        'unique'    => [],
        'boolean'   => [],
        'datetime'  => ['display' => 'dmy'],
        'html'      => [
            'toolbar'       => false,
            'blockTag'      => 'p',
            'customToolbar' => [
                'core'     => [],
                'format'   => [],
                'justify'  => [],
                'lists'    => [],
                'linking'  => [],
                'history'  => [],
                'styles'   => [],
                'aa'       => false,
                'image'    => false,
                'sv'       => false,
                'language' => false,
                'cm'       => false,
                'search'   => false,
                'table'    => false,
            ],
            'customStyles'  => [],
        ],
        'integer'   => [
            'suffix'    => '',
            'increment' => 1,
            'minimum'   => 0,
            'maximum'   => 100,
        ],
        'number'    => [
            'suffix'    => '',
            'increment' => 1,
            'minimum'   => 0,
            'maximum'   => 100,
        ],
        'pageid'    => ['multiple' => false],
        'recordset' => ['fields' => []],
        'selection' => [
            'multiple' => false,
            'options'  => [],
        ],
        'text'      => ['multiline' => false],
        'userid'    => ['multiple' => false],
        'image'     => [
            'maxFileSize'     => null,
            'dimensions'      => [],
            'decorative'      => false,
            'constraint'      => 0,
            'isWebAccessible' => false,
        ],
        'file'      => [
            'maxFileSize'       => null,
            'allowedExtensions' => [],
            'isWebAccessible'   => false,
        ],
    ];


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
                $this->args['propType'] = $this->type;
                $this->args['code']     = ($args[0] ?? null);
                $this->args['type']     = ($args[1] ?? null);
            break;

            case 'delete':
                $this->args['propType'] = $this->type;
                $this->args['code']     = ($args[0] ?? null);
            break;

            case 'rename':
                $this->args['propType'] = $this->type;
                $this->args['oldCode']  = ($args[0] ?? null);
                $this->args['newCode']  = ($args[1] ?? null);
                $this->args['code']     = $this->args['newCode'];
            break;

            default:
                $this->args = $args;
            break;
        }//end switch

    }//end setArgs()


    /**
     * Validates the property code.
     *
     * @param string $code The property code.
     *
     * @return string
     * @throws CLIException When code is invalid.
     */
    private function validatedPropertyCode(string $code)
    {
        if ($code === null) {
            $eMsg = sprintf('%s property code is required.', $this->readableType);
            throw new CLIException($eMsg);
        }

        $valid = Libs\Util::isValidStringid($code);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s property code provided', $this->readableType);
            throw new CLIException($eMsg);
        }

        $property = $this->storeDir.$code.'.json';
        if (file_exists($property) === true) {
            throw new CLIException('Property Code is already in use');
        }

        return $code;

    }//end validatedPropertyCode()


    /**
     * Adds a new property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function add()
    {
        if ($this->args['code'] === null) {
            throw new CLIException('Property Code is required');
        } else if ($this->args['type'] === null) {
            throw new CLIException('Property type is required');
        }

        try {
            $this->validatedPropertyCode($this->args['code']);
            if (is_dir($this->storeDir) === false) {
                Libs\FileSystem::mkdir($this->storeDir, true);
            }

            $propertyFile      = $this->storeDir.$this->args['code'].'.json';
            $propData          = $this->defaultSettings[$this->args['type']];
            $propData['title'] = $this->args['code'];
            file_put_contents(
                $propertyFile,
                Libs\Util::jsonEncode(
                    [
                        'type'     => $this->args['type'],
                        'settings' => $propData,
                    ]
                )
            );
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
            throw new CLIException('Original and New codes are required.');
        } else if ($this->args['newCode'] === null) {
            throw new CLIException('New code is required.');
        }

        try {
            $this->validatedPropertyCode($this->args['newCode']);
            $oldDir = $this->storeDir.$this->args['oldCode'].'.json';
            $newDir = $this->storeDir.$this->args['newCode'].'.json';
            Libs\FileSystem::move($oldDir, $newDir);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end rename()


    /**
     * Deletes a property.
     *
     * @return void
     * @throws CLIException When an error occurs.
     */
    public function delete()
    {
        $msg = Terminal::formatText(
            'This will delete the property.',
            ['bold']
        );
        $this->confirmAction($msg);

        try {
            $propertyFile = $this->storeDir.$this->args['code'].'.json';
            if (file_exists($propertyFile) === false) {
                throw new CLIException(
                    sprintf(
                        '%s property doesn\'t exist.',
                        $this->args['code']
                    )
                );
            }

            Libs\FileSystem::delete($propertyFile);
        } catch (\Exception $e) {
            throw new CLIException($e->getMessage());
        }

    }//end delete()


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
                'action'      => sprintf('perspective [-p] add property %s', $type),
                'description' => 'Adds a new property to the project',
                'arguments'   => [
                    'required' => ['propertyCode' => 'The property code for the new property.'],
                ],
            ],
            'delete' => [
                'action'      => sprintf('perspective [-p] delete property %s', $type),
                'description' => 'Deletes a  property from the project',
                'arguments'   => [
                    'required' => ['propertyCode' => 'The property code for the new property.'],
                ],
            ],
            'rename' => [
                'action'      => sprintf('perspective [-p] rename property %s', $type),
                'description' => 'Renames a property in the project',
                'arguments'   => [
                    'required' => [
                        'oldPropertyCode' => 'The code for the property.',
                        'newPropertyCode' => 'The new code for the property.',
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
                        'Usage for: perspective <action> property %s <arguments>',
                        $type
                    )
                )
            );
        }//end if

        $this->printHelpToScreen($actions, $filter);

    }//end printHelp()


}//end class
