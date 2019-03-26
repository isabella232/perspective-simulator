<?php
/**
 * Simulator Handler class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */
namespace PerspectiveSimulator;

use \PerspectiveSimulator\Libs;
use \PerspectiveAPI\Exception\InvalidDataException;

/**
 * SimulatorHandler Class
 */
class SimulatorHandler
{

    /**
     * File path for the save file.
     *
     * @var string
     */
    private $saveFile = '';

    /**
     * Instance of simulator filesystem storage.
     *
     * @var object.
     */
    private static $simulator = null;

    /**
     * Sequence of data record ids.
     *
     * @var integer
     */
    private $dataRecordSequence = 0;

    /**
     * Sequence of user ids.
     *
     * @var integer
     */
    private $userSequence = 0;

    /**
     * Sequence of user group ids.
     *
     * @var integer
     */
    private $userGroupSequence = 0;

    /**
     * Sequence of property ids.
     *
     * @var integer
     */
    private $propidSequence = 0;

    /**
     * Local cache of loaded properties.
     *
     * @var array
     */
    private $properties = [
        'data'    => [],
        'user'    => [],
        'project' => [],
    ];

    /**
     * Local cache of loaded stores.
     *
     * @var array
     */
    private $stores = [
        'data'    => [],
        'user'    => [],
        'project' => [],
    ];

    /**
     * Local cache of the loaded references.
     *
     * @var array
     */
    private $references = [];

    /**
     * Local cache of the loaded references.
     *
     * @var array
     */
    private $referenceValues = [];


    /**
     * Constructor function for simulator handler.
     */
    public function __construct()
    {
        $this->saveFile = Libs\FileSystem::getStorageDir().'/saved.json';
        if (Bootstrap::isReadEnabled() === true && file_exists($this->saveFile) === true) {
            $savedData = Libs\Util::jsonDecode(file_get_contents($this->saveFile));

            // Reload the sequneces.
            $this->dataRecordSequence = ($savedData['dataRecordSequence'] ?? 0);
            $this->userSequence       = ($savedData['userSequence'] ?? 0);
            $this->userGroupSequence  = ($savedData['userGroupSequence'] ?? 0);
            $this->propidSequence     = ($savedData['propidSequence'] ?? 0);

            // Reload created properties if any.
            $this->properties = $savedData['properties'];

            $this->referenceValues = ($savedData['referenceValues'] ?? []);

            if (isset($savedData['stores']) === true) {
                foreach ($savedData['stores'] as $type => $projects) {
                    foreach ($projects as $projectid => $stores) {
                        if (isset($this->stores[$type][$projectid]) === false) {
                            $this->stores[$type][$projectid] = [];
                        }

                        foreach ($stores as $storeCode => $storeData) {
                            if (isset($this->stores[$type][$projectid][$storeCode]) === false) {
                                $this->stores[$type][$projectid][$storeCode] = $storeData;
                            }
                        }//end foreach
                    }//end foreach
                }//end foreach
            }//end if
        }//end if

    }//end __construct()


    /**
     * Returns or instantiates a singleton instance of this console object.
     *
     * @return object
     */
    public static function getSimulator()
    {
        if (isset(self::$simulator) === false) {
            self::$simulator = new SimulatorHandler();
        }

        return self::$simulator;

    }//end getSimulator()


    /**
     * Loads the data from the filesystem
     *
     * @return void
     */
    public function load()
    {
        $prefix     = Bootstrap::generatePrefix($GLOBALS['project']);
        $projectDir = Libs\FileSystem::getProjectDir();

        $this->loadStores($prefix, $projectDir);

        // Add default user properties.
        $namespace = str_replace('-', '/', $prefix);
        $this->propidSequence++;
        $propertyid = $this->propidSequence.'.1';
        $this->properties['user'][$namespace.'/__first-name__'] = [
            'propertyid' => $propertyid,
            'type'       => 'text',
        ];

        $this->propidSequence++;
        $propertyid = $this->propidSequence.'.1';
        $this->properties['user'][$namespace.'/__last-name__'] = [
            'propertyid' => $propertyid,
            'type'       => 'text',
        ];

        $path     = substr(Libs\FileSystem::getProjectDir(), 0, -4);
        $composer = $path.'/composer.json';
        if (file_exists($composer) === true) {
            $requirements     = [];
            $composerContents = Libs\Util::jsonDecode(file_get_contents($composer));
            if (isset($composerContents['require']) === true) {
                $requirements = array_merge($requirements, $composerContents['require']);
            }

            if (isset($composerContents['require-dev']) === true) {
                $requirements = array_merge($requirements, $composerContents['require-dev']);
            }

            if (empty($requirements) === false) {
                foreach ($requirements as $requirement => $version) {
                    $project    = str_replace('/', '\\', $requirement);
                    $projectDir = $path.'/vendor/'.str_replace('\\', '/', $requirement).'/src';

                    if (is_dir($projectDir) === true) {
                        // Check its a Perspective project.
                        if (is_dir($projectDir.'/API') === false
                            || is_dir($projectDir.'/App') === false
                            || is_dir($projectDir.'/CDN') === false
                            || is_dir($projectDir.'/CustomTypes') === false
                        ) {
                            continue;
                        }

                        $composerFile = $path.'/vendor/'.$requirement.'/composer.json';
                        if (file_exists($composerFile) === true) {
                            $dependencyComposer = Libs\Util::jsonDecode(file_get_contents($composerFile));
                            foreach ($dependencyComposer['autoload']['psr-4'] as $namespace => $dir) {
                                if (strpos($dir, 'src') === 0) {
                                    $GLOBALS['projectDependencies'][$requirement] = $namespace;
                                    break;
                                }
                            }
                        } else {
                            // Missing composer file so lets just attempt to use the project with the requirements name.
                            $GLOBALS['projectDependencies'][$requirement] = $project.'\\';
                        }

                        $prefix = Bootstrap::generatePrefix($project);

                        $this->loadStores($prefix, $projectDir);

                        $namespace = str_replace('-', '/', $requirement);
                        $this->propidSequence++;
                        $propertyid = $this->propidSequence.'.1';
                        $this->properties['user'][$namespace.'/__first-name__'] = [
                            'propertyid' => $propertyid,
                            'type'       => 'text',
                        ];

                        $this->propidSequence++;
                        $propertyid = $this->propidSequence.'.1';
                        $this->properties['user'][$namespace.'/__last-name__'] = [
                            'propertyid' => $propertyid,
                            'type'       => 'text',
                        ];

                        $perspectiveAPIClassAliases = [
                            'PerspectiveAPI\Objects\Types\DataRecord' => $GLOBALS['projectDependencies'][$requirement].'CustomTypes\Data\DataRecord',
                            'PerspectiveAPI\Objects\Types\User'       => $GLOBALS['projectDependencies'][$requirement].'CustomTypes\User\User',
                            'PerspectiveAPI\Objects\Types\Group'      => $GLOBALS['projectDependencies'][$requirement].'CustomTypes\User\Group',
                        ];

                        if (class_exists($GLOBALS['projectDependencies'][$requirement].'CustomTypes\Data\DataRecord') === false) {
                            foreach ($perspectiveAPIClassAliases as $orignalClass => $aliasClass) {
                                class_alias($orignalClass, $aliasClass);
                            }
                        }

                        $perspectiveAPIClassAliases = [
                            'PerspectiveAPI\Authentication'                => 'Authentication',
                            'PerspectiveAPI\Email'                         => 'Email',
                            'PerspectiveAPI\Request'                       => 'Request',
                            'PerspectiveAPI\Queue'                         => 'Queue',
                            'PerspectiveAPI\Storage\StorageFactory'        => 'StorageFactory',
                            'PerspectiveAPI\Objects\Types\ProjectInstance' => 'ProjectInstance',
                            'PerspectiveAPI\Cache'                         => 'Cache',
                        ];

                        if (class_exists($GLOBALS['projectDependencies'][$requirement].'Framework\Authentication') === false) {
                            foreach ($perspectiveAPIClassAliases as $orignalClass => $aliasClass) {
                                eval('namespace '.$GLOBALS['projectDependencies'][$requirement].'Framework; class '.$aliasClass.' extends \\'.$orignalClass.' {}');
                            }
                        }
                    }//end if
                }//end foreach
            }//end if
        }//end if

    }//end load()


    /**
     * Gets the property array.
     *
     * @param string $type Optional filter for properties type.
     *
     * @return array
     */
    public function getProperties(string $type=null)
    {
        if ($type === null) {
            return $this->properties;
        }

        return ($this->properties[$type] ?? []);

    }//end getProperties()


    /**
     * Gets the property array.
     *
     * @param array  $properties The properties to set.
     * @param string $type       Optional filter for properties type.
     *
     * @return void
     */
    public function setProperties(array $properties, string $type=null)
    {
        if ($type === null) {
            $this->properties = $properties;
        } else {
            if (isset($this->properties[$type]) === false) {
                $this->properties[$type] = [];
            }

            $this->properties[$type] = $properties;
        }

    }//end setProperties()


    /**
     * Loads the stores.
     *
     * @param string $prefix     The projects prefix.
     * @param string $projectDir The projects directory path.
     *
     * @return void
     */
    private function loadStores(string $prefix, string $projectDir)
    {
        if (file_exists($projectDir.'/stores.json') === false) {
            // No stores
            return;
        }

        $namespace  = str_replace('-', '/', $prefix);
        $storesFile = Libs\Util::jsonDecode(file_get_contents($projectDir.'/stores.json'));
        $stores     = ($storesFile['stores'] ?? []);
        $references = ($storesFile['references'] ?? []);
        // Add stores.
        foreach ($stores as $storeType => $stores) {
            if (isset($this->stores[$storeType]) === false) {
                $this->stores[$storeType] = [];
            }

            foreach ($stores as $store) {
                $storeName = $namespace.'/'.strtolower($store);
                if ($storeType === 'user') {
                    if (isset($this->stores[$storeType][$storeName]) === false) {
                        $this->stores[$storeType][$storeName] = [
                            'records'     => [],
                            'uniqueMap'   => [],
                            'usernameMap' => [],
                            'groups'      => [],
                        ];
                    }
                } else {
                    if (isset($this->stores[$storeType][$storeName]) === false) {
                        $this->stores[$storeType][$storeName] = [
                            'records'   => [],
                            'uniqueMap' => [],
                        ];
                    }
                }//end if
            }//end foreach
        }//end foreach

        // Add references.
        foreach ($references as $referenceCode => $reference) {
            $referenceCode = $namespace.'/'.strtolower($referenceCode);
            if (isset($this->references[$referenceCode]) === false) {
                $this->references[$referenceCode] = $reference;

                $cardinality = '';
                if ($reference['source']['multiple'] === true) {
                    $cardinality .= '1:';
                } else {
                    $cardinality .= 'M:';
                }

                if ($reference['target']['multiple'] === true) {
                    $cardinality .= '1';
                } else {
                    $cardinality .= 'M';
                }

                $this->references[$referenceCode]['cardinality'] = $cardinality;

            }
        }//end foreach

    }//end loadStores()


    /**
     * Saves data to file system.
     *
     * @return void
     */
    public function save()
    {
        if (Bootstrap::isWriteEnabled() === true) {
            $saveData = [
                'dataRecordSequence' => $this->dataRecordSequence,
                'userSequence'       => $this->userSequence,
                'userGroupSequence'  => $this->userGroupSequence,
                'propidSequence'     => $this->propidSequence,
                'stores'             => $this->stores,
                'properties'         => $this->properties,
                'referenceValues'    => $this->referenceValues,
            ];

            file_put_contents($this->saveFile, Libs\Util::jsonEncode($saveData, (JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
        }

    }//end save()


    /**
     * Gets the value of a reference.
     *
     * @param string $objectType    Type of the object.
     * @param string $storeCode     The store the object belongs to.
     * @param string $id            The id of the record.
     * @param string $referenceCode The reference code.
     *
     * @return mixed
     */
    public function getReference(string $objectType, string $storeCode, string $id, string $referenceCode)
    {
        $reference     = $this->getReferenceDefinition($objectType, $storeCode, $referenceCode);
        $referenceSide = $this->getReferenceSide($reference, $objectType, $storeCode);
        if ($referenceSide === 'source') {
            $valueType = $reference['targetType'];
            $results   = $this->getReferenceValueBySource($reference, $referenceCode, $id);
        } else if ($referenceSide === 'target') {
            $valueType = $reference['sourceType'];
            $results   = $this->getReferenceValueByTarget($reference, $referenceCode, $id);
        }

        if ($results === null) {
            return null;
        }

        $references = [];
        $multiple   = is_array($results);
        if ($multiple === false) {
            $results = [$results];
        }

        foreach ($results as $result) {
            if ($valueType === 'user') {
                list($userDetails, $userStore) = $this->getUserById($result);
                $references[]                  = [
                    'id'         => $result,
                    'objectType' => 'user',
                    'storeCode'  => $userStore,
                    'typeClass'  => $userDetails['typeClass'],
                    'username'   => $userDetails['username'],
                    'firstName'  => $userDetails['firstName'],
                    'lastName'   => $userDetails['lastName'],
                ];
            } else if ($valueType === 'data') {
                list($dataRecord, $dataStore) = $this->getDataRecordById($result);
                $references[]                 = [
                    'id'         => $result,
                    'objectType' => 'data',
                    'storeCode'  => $dataStore,
                    'typeClass'  => $dataRecord['typeClass'],
                ];
            }
        }//end foreach

        if ($multiple === false) {
            $references = $references[0];
        }

        return $references;

    }//end getReference()


    /**
     * Gets the reference values of the source.
     *
     * @param array  $reference     The reference details.
     * @param string $referenceCode The ID of the reference.
     * @param string $value         The source value.
     *
     * @return null|string|array
     */
    private function getReferenceValueBySource(array $reference, string $referenceCode, string $value)
    {
        $referenceValue = $this->searchReferences($referenceCode, $reference, $value);
        if (empty($referenceValue) === true) {
            return null;
        }

        if ($reference['cardinality'] === '1:1') {
            return $referenceValue[0];
        } else {
            return $referenceValue;
        }

    }//end getReferenceValueBySource()


    /**
     * Gets the reference values of the target.
     *
     * @param array  $reference     The reference details.
     * @param string $referenceCode The ID of the reference.
     * @param string $value         The target value.
     *
     * @return null|string|array
     */
    private function getReferenceValueByTarget(array $reference, string $referenceCode, string $value)
    {
        $referenceValue = $this->searchReferences($referenceCode, $reference, null, [$value]);
        if (empty($referenceValue) === true) {
            return null;
        }

        if ($reference['cardinality'] === 'M:M') {
            foreach ($referenceValue as $targetValue) {
                $result[] = $targetValue;
            }
        } else {
            $result = $referenceValue[0];
        }

        return $result;

    }//end getReferenceValueByTarget()


    /**
     * Searches for all references values.
     *
     * @param string $referenceCode The reference code.
     * @param array  $reference     The reference information
     * @param array  $sourceValue   The source values we are searching for.
     * @param array  $targetValue   The target values we are searching for.
     *
     * @return array
     */
    private function searchReferences(
        string $referenceCode,
        array $reference,
        string $sourceValue=null,
        array $targetValue=null
    ) {
        $values = [];
        $stores = $this->stores;

        if ($sourceValue !== null) {
            if (isset($this->referenceValues[$referenceCode][$sourceValue]) === true) {
                $values = $this->referenceValues[$referenceCode][$sourceValue];
            }
        } else if ($targetValue !== null) {
            if (isset($this->referenceValues[$referenceCode]) === true) {
                foreach ($this->referenceValues[$referenceCode] as $sVal => $targetValues) {
                    foreach ($targetValue as $tVal) {
                        if (in_array($tVal, $targetValues) === true) {
                            $values[] = $sVal;
                        }
                    }
                }
            }
        }

        if (empty($values) === true) {
            return null;
        }

        return $values;

    }//end searchReferences()


    /**
     * Adds the value of a reference.
     *
     * @param string $objectType    Type of the object.
     * @param string $storeCode     The store the object belongs to.
     * @param string $id            The id of the record.
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       The objects to store as reference.
     *
     * @return void
     */
    public function addReference(string $objectType, string $storeCode, string $id, string $referenceCode, $objects)
    {
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        $reference = $this->getReferenceDefinition($objectType, $storeCode, $referenceCode);
        list(
            $referenceid,
            $sourceValue,
            $targetValue
        )          = $this->resolveReferenceArguments($objectType, $id, $storeCode, $referenceCode, $objects);

        if (isset($this->referenceValues[$referenceCode]) === false) {
            $this->referenceValues[$referenceCode] = [];
        }

        $validateRef = function ($source, $target) use ($reference, $referenceCode) {
            if ($reference['cardinality'] === '1:1' && count($target) > 1) {
                $cardinalityErrMsg = 'Invalid target value for 1:1 cardinality. Expecting one but got %s';
                throw new InvalidDataException(sprintf($cardinalityErrMsg, implode(',', $target)));
            }

            $cardinalityErrMsg = 'Failed to add reference value since the source (%s) already has the value (%s) in %s relationship';
            $existing          = $this->searchReferences($referenceCode, $reference, $source);
            if (empty($existing) === false) {
                $target = array_diff($target, $existing);
                if (empty($target) === true) {
                    // The current value includes all values in $target. Nothing else to do here.
                    return;
                }

                if ($reference['cardinality'] === '1:1') {
                    throw new InvalidDataException(
                        sprintf(
                            $cardinalityErrMsg,
                            $source,
                            implode(',', array_keys($existing)),
                            $reference['cardinality']
                        )
                    );
                }
            }

            if ($reference['cardinality'] === '1:1' || $reference['cardinality'] === '1:M') {
                foreach ($target as $tVal) {
                    $existingTargetValues = $this->getReferenceValueByTarget($reference, $referenceCode, $tVal);
                    if (empty($existingTargetValues) === false) {
                        throw new InvalidDataException(
                            sprintf(
                                $cardinalityErrMsg,
                                $existingTargetValues,
                                $tVal,
                                $reference['cardinality']
                            )
                        );
                    }
                }
            }//end if

            return true;
        };

        if (is_array($sourceValue) === true) {
            foreach ($sourceValue as $sVal) {
                if ($validateRef($sVal, $targetValue) === true) {
                    if (isset($this->referenceValues[$referenceCode][$sVal]) === false) {
                        $this->referenceValues[$referenceCode][$sVal] = [];
                    }

                    $this->referenceValues[$referenceCode][$sVal][] = $targetValue;
                }
            }
        } else {
            if ($validateRef($sourceValue, $targetValue) === true) {
                if (isset($this->referenceValues[$referenceCode][$sourceValue]) === false) {
                    $this->referenceValues[$referenceCode][$sourceValue] = [];
                }

                foreach ($targetValue as $tVal) {
                    $this->referenceValues[$referenceCode][$sourceValue][] = $tVal;
                }
            }
        }//end if

    }//end addReference()


    /**
     * Adds the value of a reference.
     *
     * @param string $objectType    Type of the object.
     * @param string $storeCode     The store the object belongs to.
     * @param string $id            The id of the record.
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       The objects to store as reference.
     *
     * @return void
     */
    public function setReference(string $objectType, string $storeCode, string $id, string $referenceCode, $objects)
    {
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        $reference = $this->getReferenceDefinition($objectType, $storeCode, $referenceCode);
        list(
            $referenceid,
            $sourceValue,
            $targetValue
        )          = $this->resolveReferenceArguments($objectType, $id, $storeCode, $referenceCode, $objects);

        if (isset($this->referenceValues[$referenceCode]) === false) {
            $this->referenceValues[$referenceCode] = [];
        }

        $referenceSide = $this->getReferenceSide($reference, $objectType, $storeCode);
        if (in_array($referenceSide, ['source', 'target']) === false) {
            throw new InvalidDataException('Reference side not specified');
        }

        if (is_array($sourceValue) === false) {
            $sourceValue = [$sourceValue];
        }

        $sourceValue = array_unique($sourceValue);
        $targetValue = array_unique($targetValue);
        if ($reference['cardinality'] === '1:M' && count($sourceValue) !== 1) {
            throw new InvalidDataException('Expecting single source value in 1:M or 1:1 relationship');
        }

        if ($reference['cardinality'] === '1:1' && (count($sourceValue) !== 1 || count($targetValue) !== 1)) {
            throw new InvalidDataException('Expecting single target value in 1:1 relationship');
        }

        if ($referenceSide === 'target' && count($targetValue) !== 1) {
            // Because our API uses object notation, the reference side is always a single value (the ID of the object).
            throw new InvalidDataException('Expecting single target value in set reference');
        }

        if ($referenceSide === 'source' && count($sourceValue) !== 1) {
            // Because our API uses object notation, the reference side is always a single value (the ID of the object).
            throw new InvalidDataException('Expecting single source value in set reference');
        }

        $cardinalityErrMsg = 'Failed to add reference value since the source (%s) already has the value (%s) in %s relationship';
        if ($referenceSide === 'source') {
            if ($reference['cardinality'] === '1:1' || $reference['cardinality'] === '1:M') {
                foreach ($targetValue as $tVal) {
                    $existingTargetValues = $this->getReferenceValueByTarget($reference, $referenceCode, $tVal);
                    if (empty($existingTargetValues) === false) {
                        throw new InvalidDataException(
                            sprintf(
                                $cardinalityErrMsg,
                                $existingTargetValues,
                                $tVal,
                                $reference['cardinality']
                            )
                        );
                    }
                }
            }//end if

            foreach ($sourceValue as $sVal) {
                $this->referenceValues[$referenceCode][$sVal] = $targetValue;
            }
        } else {
            if ($reference['cardinality'] === '1:1') {
                $existingSourceValue = $this->getReferenceValueBySource($reference, $referenceCode, $sourceValue[0]);
                if (empty($existingSourceValue) === false) {
                    throw new InvalidDataException(
                        sprintf(
                            $cardinalityErrMsg,
                            $sourceValue[0],
                            $existingSourceValue,
                            $reference['cardinality']
                        )
                    );
                }
            }

            $existingTargetValues = $this->getReferenceValueByTarget($reference, $referenceCode, $targetValue[0]);
            if ($existingTargetValues !== null) {
                if (is_array($existingTargetValues) === false) {
                    $existingTargetValues = [$existingTargetValues];
                }

                foreach ($existingTargetValues as $existingTargetValue) {
                    $this->referenceValues[$referenceCode][$existingTargetValue] = array_diff($this->referenceValues[$referenceCode][$existingTargetValue], $targetValue);
                    if (empty($this->referenceValues[$referenceCode][$existingTargetValue]) === true) {
                        unset($this->referenceValues[$referenceCode][$existingTargetValue]);
                    }
                }
            }

            foreach ($sourceValue as $sVal) {
                $this->referenceValues[$referenceCode][$sVal][] = $targetValue[0];
            }

            // set
        }//end if

    }//end setReference()


    /**
     * Deletes the value of a reference.
     *
     * @param string $objectType    Type of the object.
     * @param string $storeCode     The store the object belongs to.
     * @param string $id            The id of the record.
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       The objects to store as reference.
     *
     * @return void
     */
    public function deleteReference(string $objectType, string $storeCode, string $id, string $referenceCode, $objects)
    {
        if (is_array($objects) === false) {
            $objects = [$objects];
        }

        list(
            $referenceid,
            $sourceValue,
            $targetValue
        ) = self::resolveReferenceArguments($objectType, $id, $storeCode, $referenceCode, $objects);

        if (is_array($sourceValue) === true) {
            foreach ($sourceValue as $sVal) {
                $this->referenceValues[$referenceCode][$sVal] = array_diff($this->referenceValues[$referenceCode][$sVal], $targetValue);
                if (empty($this->referenceValues[$referenceCode][$sVal]) === true) {
                    unset($this->referenceValues[$referenceCode][$sVal]);
                }
            }
        } else {
            $this->referenceValues[$referenceCode][$sourceValue] = array_diff($this->referenceValues[$referenceCode][$sourceValue], $targetValue);
            if (empty($this->referenceValues[$referenceCode][$sourceValue]) === true) {
                unset($this->referenceValues[$referenceCode][$sourceValue]);
            }
        }

    }//end deleteReference()


    /**
     * Gets the reference definition.
     *
     * @param string $objectType  The object type we are using to reference.
     * @param string $storeCode   The code of the store.
     * @param string $referenceid The id of the reference we getting.
     *
     * @return array
     */
    private function getReferenceDefinition(string $objectType, string $storeCode, string $referenceid)
    {
        $reference = [];
        if (isset($this->references[$referenceid]) === true) {
            $reference = $this->references[$referenceid];
            if ($reference['cardinality'] === 'M:1') {
                $reference['cardinality'] = '1:M';
                $sourceTypeArg            = $reference['source']['type'];
                $sourceCodeArg            = $reference['source']['code'];
                $reference['sourceType']  = $reference['target']['type'];
                $reference['sourceCode']  = $reference['target']['code'];
                $reference['targetType']  = $sourceTypeArg;
                $reference['targetCode']  = $sourceCodeArg;
            } else {
                $reference['sourceType'] = $reference['source']['type'];
                $reference['sourceCode'] = $reference['source']['code'];
                $reference['targetType'] = $reference['target']['type'];
                $reference['targetCode'] = $reference['target']['code'];
            }
        }

        return $reference;

    }//end getReferenceDefinition()


    /**
     * Resolve reference values.
     *
     * The main purpose is to work out which side of the relationship is the source and the target as per the reference
     * definition. This function also validates the objects in the process.
     *
     * @param string $referenceCode The reference code.
     * @param array  $objects       Set of objects (User or DataRecord).
     *
     * @return array
     * @throws InvalidDataException Thrown when the cardinality and the number of elements in source or target is not valid.
     */
    private function resolveReferenceArguments(string $objectType, string $id, string $storeCode, string $referenceCode, array $objects)
    {
        $reference = $this->getReferenceDefinition($objectType, $storeCode, $referenceCode);
        if (empty($reference) === true) {
            throw new InvalidDataException(sprintf('Unknown referenceid: %s', $referenceCode));
        }

        $sourceValue = [];
        $targetValue = [];

        // Categorise the given objects into source and target values depending on their side in relationship.
        foreach ($objects as $object) {
            $referenceSide = $this->getReferenceSide($reference, $object['objectType'], $object['storeCode']);
            if ($referenceSide === 'source') {
                $sourceValue[] = $object['id'];
            } else if ($referenceSide === 'target') {
                $targetValue[] = $object['id'];
            }
        }

        $referenceSide = $this->getReferenceSide($reference, $objectType, $storeCode);
        if ($referenceSide === 'source') {
            $sourceValue[] = $id;
        } else if ($referenceSide === 'target') {
            $targetValue[] = $id;
        }

        // Validate the number of source or target based on the cardinality setting.
        $errorMsg = 'Expecting single %s value in %s cardinality, but %s given';
        if ($reference['cardinality'] === '1:1') {
            if (count($sourceValue) !== 1) {
                throw new InvalidDataException(
                    sprintf($errorMsg, 'source', $reference['cardinality'], implode(',', $sourceValue))
                );
            }

            if (count($targetValue) !== 1) {
                throw new InvalidDataException(
                    sprintf($errorMsg, 'target', $reference['cardinality'], implode(',', $targetValue))
                );
            }
        } else if ($reference['cardinality'] === '1:M') {
            if (count($sourceValue) !== 1) {
                throw new InvalidDataException(
                    sprintf($errorMsg, 'source', $reference['cardinality'], implode(',', $sourceValue))
                );
            }
        }

        if ($referenceSide === 'source') {
            if ($sourceValue[0] !== $id) {
                throw new InvalidDataException('The target must be the object itself');
            }

            return [
                $referenceCode,
                $id,
                $targetValue,
                $referenceSide,
            ];
        } else {
            if ($targetValue[0] !== $id) {
                throw new InvalidDataException('The source must be the object itself');
            }

            if ($reference['cardinality'] === 'M:M' && count($sourceValue) > 1) {
                return [
                    $referenceCode,
                    $sourceValue,
                    $targetValue,
                    $referenceSide,
                ];
            } else {
                return [
                    $referenceCode,
                    $sourceValue[0],
                    $targetValue,
                    $referenceSide,
                ];
            }
        }//end if

    }//end resolveReferenceArguments()


    /**
     * Gets the reference side (source or target) to validate the reference.
     *
     * @param array  $reference   The reference data.
     * @param object $objectType  The object type we are using to reference.
     * @param string $storageCode The code of the store.
     *
     * @return string
     * @throws \Exception When invalid reference.
     */
    private function getReferenceSide(array $reference, string $objectType, string $storageCode)
    {
        if (ucfirst($objectType) === 'User') {
            $storageClass = 'user';
        } else if (ucfirst($objectType) === 'Data') {
            $storageClass = 'data';
        } else {
            throw new \Exception(
                sprintf('Invalid referenced object: invalid object type: %s'),
                $objectType
            );
        }

        $parts = explode('/', $storageCode);

        if ($reference['sourceCode'] !== null) {
            $reference['sourceCode'] = $parts[0].'/'.$parts[1].'/'.$reference['sourceCode'];
        }

        if ($reference['targetCode'] !== null) {
            $reference['targetCode'] = $parts[0].'/'.$parts[1].'/'.$reference['targetCode'];
        }

        if ($reference['sourceType'] === $storageClass && ($reference['sourceCode'] === $storageCode || $reference['sourceCode'] === null)) {
            return 'source';
        } else if ($reference['targetType'] === $storageClass && ($reference['targetCode'] === $storageCode || $reference['targetCode'] === null)) {
            return 'target';
        } else {
            throw new \Exception(sprintf('Invalid referenced object: %s', $objectType));
        }

    }//end getReferenceSide()


    /**
     * Gets the users from a group.
     *
     * @param string $storeCode The store the users and group belongs to.
     * @param string $groupid   The groupid.
     *
     * @return array
     */
    public function getGroupMembers(string $storeCode, string $groupid)
    {
        $users   = $this->stores['user'][$storeCode]['records'];
        $members = array_filter(
            $users,
            function ($record) use ($groupid) {
                $groups = array_keys($record['groups']);
                return in_array($groupid, $groups);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return $members;

    }//end getGroupMembers()


    /**
     * Sets the group name.
     *
     * @param string $storeCode The store the group belongs to.
     * @param string $id        The id of the group.
     * @param string $name      The name of the group.
     */
    public function setGroupName(string $storeCode, string $id, string $name)
    {
        if (isset($this->stores['user'][$storeCode]['groups'][$id]) === true) {
            $this->stores['user'][$storeCode]['groups'][$id] = $name;
        }

    }//end setGroupName()


    /**
     * Set username.
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $id        The id of the user.
     * @param string $username  The username.
     *
     * @return void
     */
    public static function setUsername(string $id, string $storeCode, string $username)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            $this->stores['user'][$storeCode]['records'][$id]['username'] = $username;
        }

    }//end setUsername()


    /**
     * Set first name
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $id        The id of the user.
     * @param string $firstName The first name of the user.
     *
     * @return void
     */
    public static function setUserFirstName(string $id, string $storeCode, string $firstName)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            $this->stores['user'][$storeCode]['records'][$id]['properties']['__first-name__'] = $firstName;
        }

    }//end setUserFirstName()


    /**
     * Set last name
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $id        The id of the user.
     * @param string $lastName  The last name of the user.
     *
     * @return void
     */
    public static function setUserLastName(string $id, string $storeCode, string $lastName)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            $this->stores['user'][$storeCode]['records'][$id]['properties']['__last-name__'] = $lastName;
        }

    }//end setUserLastName()


    /**
     * Gets the users groups.
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $id        The id of the user.
     *
     * @return mixed
     */
    public function getUserGroups(string $storeCode, string $id)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            return array_keys($this->stores['user'][$storeCode]['records'][$id]['groups']);
        }

        return false;

    }//end getUserGroups()


    /**
     * Assign an user to parent groups.
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $id        The id of the user.
     * @param mixed  $groupid   Parent user groups to assign the user to.
     *
     * @return void
     */
    public static function addUserToGroup(string $id, string $storeCode, string $groupid)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            $this->stores['user'][$storeCode]['records'][$id]['groups'][$groupid] = true;
            return true;
        }

        return false;

    }//end addUserToGroup()


    /**
     * Remove an user from specified parent groups.
     *
     * @param mixed $groupid Parent user groups to remove the user from.
     *
     * @return void
     */
    public static function removeUserFromGroup(string $id, string $storeCode, string $groupid)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$id]) === true) {
            unset($this->stores['user'][$storeCode]['records'][$id]['groups'][$groupid]);
            return true;
        }

        return false;

    }//end removeUserFromGroup()


    /**
     * Gets a property value.
     *
     * @param string $objectType   The object type eg, data, user.
     * @param string $storeCode    The store code.
     * @param string $id           The id of the data record.
     * @param string $propertyCode The property we want the value of.
     *
     * @return mixed
     */
    public function getPropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyCode);
        if (isset($this->properties[$objectType][$propid]) === false) {
            return null;
        }

        $property = $this->properties[$objectType][$propid];
        if ($objectType === 'project'
            && isset($this->stores[$objectType][$property['propertyid']]) === true
        ) {
            return $this->stores[$objectType][$property['propertyid']];
        } else if (isset($this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']]) === true) {
            return $this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']];
        }

        return null;

    }//end getPropertyValue()


    /**
     * Sets a property value.
     *
     * @param string $objectType   The object type eg, data, user.
     * @param string $storeCode    The store code.
     * @param string $id           The id of the data record.
     * @param string $propertyCode The property we are setting.
     * @param mixed  $value        The value of the property.
     *
     * @return void
     */
    public function setPropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode, $value)
    {
        if ($value === null) {
            throw new \Exception('Property value violates not-null constraint');
        }

        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyCode);
        if (isset($this->properties[$objectType][$propid]) === false) {
            $this->propidSequence++;
            $propertyid = $this->propidSequence.'.1';
            $this->properties[$objectType][$propid] = [
                'propertyid' => $propertyid,
                'type'       => $propType,
            ];
        }

        $property = $this->properties[$objectType][$propid];

        if ($property['type'] !== $propType) {
            throw new \Exception(sprintf('Invalid property type expected "%1$s" got "%2$s"', $property['type'], $propType));
        }

        if ($property['type'] === 'unique') {
            $current = ($this->stores[$objectType][$storeCode]['uniqueMap'][$property['propertyid']][$value] ?? null);
            if ($current !== null) {
                throw new \Exception('Unique value "'.$value.'" is already in use');
            }

            if ($objectType === 'project') {
                $this->stores[$objectType][$property['propertyid']] = $id;
            } else {
                $this->stores[$objectType][$storeCode]['uniqueMap'][$property['propertyid']][$value] = $id;
            }
        } else if ($property['type'] === 'image' || $property['type'] === 'file') {
            $value = $this->prepareFileImagePropertyValue($value, ucfirst($objectType), $propid);
        }

        if ($objectType === 'project') {
            $this->stores[$objectType][$property['propertyid']] = $value;
        } else {
            $this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']] = $value;
        }

    }//end setPropertyValue()


    /**
     * Deletes a property value.
     *
     * @param string $objectType   The object type eg, data, user.
     * @param string $storeCode    The store code.
     * @param string $id           The id of the data record.
     * @param string $propertyCode The property we are setting.
     *
     * @return void
     */
    public function deletePropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyCode);
        $property                = $this->properties[$objectType][$propid];

        if ($objectType === 'project'
            && isset($this->stores[$objectType][$property['propertyid']]) === true
        ) {
            unset($this->stores[$objectType][$property['propertyid']]);
        } else if (isset($this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']]) === true) {
            unset($this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']]);
        }

    }//end deletePropertyValue()


    /**
     * Prepares the value of a file and image property.
     *
     * @param array  $value        The value of the property to validate and prepare.
     * @param string $propertyType The type of the property.
     *
     * @return array
     * @throws \Exception Thrown when the value array is invalid.
     */
    private function prepareFileImagePropertyValue($value, $propertyType, $propertyCode)
    {
        if (is_array($value) === true) {
            // Expecting the structure of file upload array.
            $requiredFields = [
                'name',
                'type',
                'tmp_name',
                'error',
                'size',
            ];
            foreach ($requiredFields as $field) {
                if (array_key_exists($field, $value) === false) {
                    $errMsg = sprintf(
                        'Expecting \'%s\' field but not found in the value.',
                        $field
                    );
                    throw new \Exception($errMsg);
                }
            }

            $uploadedFilepath = Libs\FileSystem::getStorageDir().'/properties/'.$propertyType;
            if (is_dir($uploadedFilepath) === false) {
                Libs\FileSystem::mkdir($uploadedFilepath, true);
            }

            $ext            = Libs\FileSystem::getExtension($value['name']);
            $targetFilepath = $uploadedFilepath.'/'.$propertyCode.'.'.$ext;
            if (move_uploaded_file($value['tmp_name'], $targetFilepath) === false) {
                throw new \Exception('Failed to get the upload file.');
            }

            return '/property/'.$GLOBALS['projectPath'].'/'.$propertyType.'/'.$propertyCode.'.'.$ext;
        } else if (is_string($value) === true) {
            // Expecting the base64 string.
            if (preg_match('#^data:[a-z]+/([a-z]+);base64,[\w=+/]++#', $value) !== 1) {
                throw new \Exception('The string value for File/Image property should be a valid base64 string.');
            }

            return $value;
        }//end if

    }//end prepareFileImagePropertyValue()


    /**
     * Gets the children for an object.
     *
     * @param string  $objectType The object type.
     * @param string  $storeCode  The store the object belongs to.
     * @param string  $id         The id of the record.
     * @param integer $depth      The depth to get.
     *
     * @return array
     */
    public function getChildren(string $objectType, string $storeCode, string $id, int $depth=null)
    {
        if (isset($this->stores[$objectType][$storeCode]['records'][$id]) === false) {
            return [];
        }

        if ($depth !== null) {
            if ($depth === 0) {
                return [];
            }

            $depth--;
        }

        $children = [];
        foreach ($this->stores[$objectType][$storeCode]['records'][$id]['children'] as $childid => $child) {
            $children[$childid] = [
                'depth'    => $this->stores[$objectType][$storeCode]['records'][$childid]['depth'],
                'children' => [],
            ];

            if ($depth !== 0) {
                $children[$childid]['children'] = $this->getChildren($objectType, $storeCode, $childid, $depth);
            }
        }

        return $children;

    }//end getChildren()


    /**
     * Gets the parents for an object.
     *
     * @param string  $objectType The object type.
     * @param string  $storeCode  The store the object belongs to.
     * @param string  $id         The id of the record.
     * @param integer $depth      The depth to get.
     *
     * @return array
     */
    public function getParents(string $objectType, string $storeCode, string $id, int $depth=null)
    {
        if (isset($this->stores[$objectType][$storeCode]['records'][$id]['parent']) === false) {
            return [];
        }

        if ($depth !== null) {
            if ($depth === 0) {
                return [];
            }

            $depth--;
        }

        $parents = [];
        if ($this->stores[$objectType][$storeCode]['records'][$id]['parent'] !== null) {
            $parentid           = $this->stores[$objectType][$storeCode]['records'][$id]['parent'];
            $parents[$parentid] = [
                'depth'   => $this->stores[$objectType][$storeCode]['records'][$id]['depth'],
                'parents' => [],
            ];

            if ($depth !== 0) {
                $parents[$parentid]['parents'] = $this->getParents($objectType, $storeCode, $parentid, $depth);
            }
        }

        return $parents;

    }//end getParents()


    /**
     * Creates a Data Record.
     *
     * @param string $storeCode  The store the data record will belong to.
     * @param string $customType The type of the data record.
     * @param string $parent     The parent of the data record.
     *
     * @return mixed
     */
    public function createDataRecord(string $storeCode, string $customType, string $parent=null)
    {
        if ($customType === null) {
            $customType = '\PerspectiveAPI\Objects\Types\DataRecord';
        } else {
            if (strpos($storeCode, strtolower($GLOBALS['project'])) === 0) {
                $customType = '\\'.$GLOBALS['projectNamespace'].'CustomTypes\Data\\'.basename($customType);
            } else {
                $packageName = str_replace('/'.basename($storeCode), '', $storeCode);
                $requirement = $GLOBALS['projectDependencies'][$packageName];
                $customType  = '\\'.$requirement.'CustomTypes\Data\\'.basename($customType);
            }
        }

        if ($parent !== null && isset($this->stores['data'][$storeCode]['records'][$parent]) === false) {
            return null;
        }

        $this->dataRecordSequence++;
        $recordid = $this->dataRecordSequence.'.1';

        $this->stores['data'][$storeCode]['records'][$recordid] = [
            'id'        => $recordid,
            'typeClass' => $customType,
            'depth'     => 1,
            'children'  => [],
            'parent'    => $parent,
        ];

        if ($parent !== null) {
            $this->stores['data'][$storeCode]['records'][$parent]['children'][$recordid] = $this->stores['data'][$storeCode]['records'][$recordid];
            $this->stores['data'][$storeCode]['records'][$recordid]['depth']            += $this->stores['data'][$storeCode]['records'][$parent]['depth'];
        }

        return $this->stores['data'][$storeCode]['records'][$recordid];

    }//end createDataRecord()


    /**
     * Gets a data record.
     *
     * @param string $storeCode The store code the data record belongs to.
     * @param string $id        The id of the data record.
     *
     * @return mixed
     */
    public function getDataRecord(string $storeCode, string $id)
    {
        if (isset($this->stores['data'][$storeCode]['records'][$id]) === true) {
            return $this->stores['data'][$storeCode]['records'][$id];
        }

        return null;

    }//end getDataRecord()


    /**
     * Gets unique data record value.
     *
     * @param string $objectType Getting a dataRecord|user|group.
     * @param string $storeCode  The store we are looking in.
     * @param string $propertyid The unique property code.
     * @param string $value      The value.
     *
     * @return mixed.
     */
    public function getObjectInfoByUniquePropertyValue(string $objectType, string $storeCode, string $propertyid, string $value)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyid);

        $propertyType = $objectType;
        if ($objectType === 'group') {
            $propertyType = 'user';
        }

        if (isset($this->properties[$propertyType][$propid]) === false) {
            return null;
        }

        $property = $this->properties[$propertyType][$propid];
        $id       = ($this->stores[$propertyType][$storeCode]['uniqueMap'][$property['propertyid']][$value] ?? null);
        if ($id === null) {
            return null;
        }

        if ($objectType === 'user') {
            return $this->getUser ($storeCode, $id);
        } else if ($objectType === 'group') {
            return $this->getGroup($storeCode, $id);
        } else {
            return $this->getDataRecord($storeCode, $id);
        }

    }//end getDataRecordByValue()


    /**
     * Creates a User.
     *
     * @param string $storeCode  The store the data record will belong to.
     * @param string $customType The type of the data record.
     * @param string $parent     The parent of the data record.
     *
     * @return mixed
     */
    public function createUser(string $storeCode, string $username, string $firstName, string $lastName, string $type=null, array $groups=[])
    {
        $this->userSequence++;

        $recordid = $this->userSequence.'.1';
        $this->stores['user'][$storeCode]['records'][$recordid] = [
            'id'        => $recordid,
            'username'  => $username,
            'typeClass' => '\PerspectiveAPI\Objects\Types\User',
            'groups'    => $groups,
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];

        $this->stores['user'][$storeCode]['usernameMap'][$username] = $recordid;

        return $this->stores['user'][$storeCode]['records'][$recordid];

    }//end createUser()


    /**
     * Creates a user group.
     *
     * @param string $storeCode The store the group will belong to.
     * @param string $groupName The name of the group.
     * @param string $type      User type code.
     *                          TODO: this is a palceholder until user types are implemented.
     * @param array  $groups    Optional. Parent user groups to assign the new user to. If left empty, user will be
     *                          created under root user group.
     *
     * @return array
     */
    public function createGroup(string $storeCode, string $groupName, string $type, array $groups=[])
    {
        $this->userGroupSequence++;
        $recordid = $this->userGroupSequence.'.1';

        $this->stores['user'][$storeCode]['groups'][$recordid] = [
            'groupid'   => $recordid,
            'groupName' => $groupName,
            'type'      => null,
            'groups'    => $groups,
        ];

        return $this->getGroup($storeCode, $recordid);

    }//end createGroup()


    /**
     * Gets a user group.
     *
     * @param string $storeCode The store the group belongs to.
     * @param string $groupid   The groupid of the group.
     *
     * @return mixed
     */
    public function getGroup(string $storeCode, string $groupid)
    {
        if (isset($this->stores['user'][$storeCode]['groups'][$groupid]) === false) {
            return null;
        }

        return $this->stores['user'][$storeCode]['groups'][$groupid];

    }//end getGroup()


    /**
     * Gets a user by username.
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $username  The username to search for.
     *
     * @return mixed
     */
    public function getUserByUsername(string $storeCode, string $username)
    {
        if (isset($this->stores['user'][$storeCode]['usernameMap'][$username]) === false) {
            return null;
        }

        return $this->getUser($storeCode, $this->stores['user'][$storeCode]['usernameMap'][$username]);

    }//end getUserByUsername()


    /**
     * Gets a user.
     *
     * @param string $storeCode The store the user belongs to.
     * @param string $userid    The userid to search for.
     *
     * @return mixed
     */
    public function getUser(string $storeCode, string $userid)
    {
        if (isset($this->stores['user'][$storeCode]['records'][$userid]) === false) {
            return null;
        }

        return $this->stores['user'][$storeCode]['records'][$userid];

    }//end getUser()


    /**
     * Gets a user by ID.
     *
     * @param string $userid The userid to search for.
     *
     * @return mixed
     */
    public function getUserById(string $userid)
    {
        $user = null;
        foreach ($this->stores['user'] as $storeCode => $store) {
            foreach ($this->stores['user'][$storeCode]['records'] as $id => $record) {
                if ($record['id'] === $userid) {
                    $user = [
                        $this->stores['user'][$storeCode]['records'][$id],
                        $storeCode,
                    ];
                    break;
                }
            }//end foreach

            if ($user !== null) {
                break;
            }
        }//end foreach

        return $user;

    }//end getUserById()


    /**
     * Gets a data record by ID.
     *
     * @param string $recordid The userid to search for.
     *
     * @return mixed
     */
    public function getDataRecordById(string $recordid)
    {
        $dataRecord = null;
        foreach ($this->stores['data'] as $storeCode => $store) {
            foreach ($this->stores['data'][$storeCode]['records'] as $id => $record) {
                if ($record['id'] === $recordid) {
                    $dataRecord = [
                        $this->stores['data'][$storeCode]['records'][$id],
                        $storeCode,
                    ];
                    break;
                }
            }//end foreach

            if ($dataRecord !== null) {
                break;
            }
        }//end foreach

        return $dataRecord;

    }//end getDataRecordById()


    /**
     * Returns the incremented value of the property.
     *
     * @param string $propertyCode The property code we are incrementing.
     * @param string $storeCode    The store code.
     * @param string $objectType   The object type.
     * @param mixed  $value        Integer|Float to increment by.
     *
     * @return integer|float
     */
    public static function incrementPropertyValue(string $propertyCode, string $storeCode, string $objectType, $value)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyid);

        $propertyType = $objectType;
        if ($objectType === 'group') {
            $propertyType = 'user';
        }

        if (isset($this->properties[$propertyType][$propid]) === false) {
            $this->propidSequence++;
            $propertyid = $this->propidSequence.'.1';
            $this->properties[$objectType][$propid] = [
                'propertyid' => $propertyid,
                'type'       => $propType,
            ];
        }

        $property = $this->properties[$propertyType][$propid];
        if ($objectType === 'project') {
            $this->stores[$objectType][$property['propertyid']] = ($this->stores[$objectType][$property['propertyid']] + $value);
        } else {
            $this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']] = ($this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']] + $value);
        }

    }//end incrementPropertyValue()


    /**
     * Returns the decremented value of the property.
     *
     * @param string $propertyCode The property code we are incrementing.
     * @param string $storeCode    The store code.
     * @param string $objectType   The object type.
     * @param mixed  $value        Integer|Float to increment by.
     *
     * @return integer|float
     */
    public static function decrementPropertyValue(string $propertyCode, string $storeCode, string $objectType, $value)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyid);

        $propertyType = $objectType;
        if ($objectType === 'group') {
            $propertyType = 'user';
        }

        if (isset($this->properties[$propertyType][$propid]) === false) {
            $this->propidSequence++;
            $propertyid = $this->propidSequence.'.1';
            $this->properties[$objectType][$propid] = [
                'propertyid' => $propertyid,
                'type'       => $propType,
            ];
        }

        $property = $this->properties[$propertyType][$propid];
        if ($objectType === 'project') {
            $this->stores[$objectType][$property['propertyid']] = ($this->stores[$objectType][$property['propertyid']] - $value);
        } else {
            $this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']] = ($this->stores[$objectType][$storeCode]['records'][$id][$property['propertyid']] - $value);
        }

    }//end decrementPropertyValue()


    /**
     * Cast data record.
     *
     * @param string $dataRecordid       The data record object id.
     * @param string $dataRecordTypeCode The data record type code.
     * @param string $storeCode    The store code.
     *
     * @return void
     */
    public function castDataRecord(string $dataRecordid, string $dataRecordTypeCode, string $storeCode)
    {
        if (strpos($storeCode, strtolower($GLOBALS['project'])) === 0) {
            $customType = '\\'.$GLOBALS['projectNamespace'].'CustomTypes\Data\\'.basename($customType);
        } else {
            $packageName = str_replace('/'.basename($storeCode), '', $storeCode);
            $requirement = $GLOBALS['projectDependencies'][$packageName];
            $customType  = '\\'.$requirement.'CustomTypes\Data\\'.basename($customType);
        }

        $this->stores['data'][$storeCode]['records'][$dataRecordid]['typeClass'] = $customType;

    }//end castDataRecord()


    /**
     * Moves a data record between parents.
     *
     * @param string $dataRecordid       The data recordid of the record we are changing the parent of.
     * @param string $parentDataRecordid The new partent id of the data record.
     * @param string $storeCode          The store code.
     *
     * @return void
     */
    public static function moveDataRecord(string $dataRecordid, string $parentDataRecordid, string $storeCode)
    {
        $this->stores['data'][$storeCode]['records'][$dataRecordid]['parent'] = $parentDataRecordid;

    }//end moveDataRecord()


}//end class
