<?php
/**
 * Connector class for Perspective Simulator to Perspective API.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */
namespace PerspectiveSimulator;

use \PerspectiveAPI\Objects\AbstractObject as AbstractObject;
use \PerspectiveAPI\Objects\Types\User as User;
use \PerspectiveAPI\Objects\Types\Group as Group;
use \PerspectiveAPI\Objects\Types\DataRecord as DataRecord;
use \PerspectiveAPI\Objects\Types\ProjectInstance as ProjectInstance;
use \PerspectiveAPI\Storage\StorageFactory as StorageFactory;

use \PerspectiveSimulator\Libs;

class SimulatorConnector implements \PerspectiveAPI\ConnectorInterface
{


    /**
     * Gets teh property's type.
     *
     * @param string $objectType   The object type.
     * @param string $propertyCode The property code.
     *
     * @return string
     */
    public static function getPropertyTypeClass(string $objectType, string $propertyCode)
    {
        list($propid, $propType) = Bootstrap::getPropertyInfo($propertyCode);
        return '\PerspectiveAPI\Property\Types\\'.ucfirst($propType);

    }//end getPropertyTypeClass()


    /**
     * Get reference.
     *
     * @param string $referenceCode The reference code.
     *
     * @return mixed
     */
    public static function getReference(string $objectType, string $id, string $storeCode, string $referenceCode)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getReference($objectType, $storeCode, $id, $referenceCode);

    }//end getReference()


    /**
     * Add reference.
     *
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       Set of objects (User or DataRecord).
     *
     * @return void
     */
    public static function addReference(string $objectType, string $id, string $storeCode, string $referenceCode, $objects)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->addReference($objectType, $storeCode, $id, $referenceCode, $objects);

    }//end addReference()


    /**
     * Set reference.
     *
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       Set of objects (User or DataRecord).
     *
     * @return void
     */
    public static function setReference(string $objectType, string $id, string $storeCode, string $referenceCode, $objects)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setReference($objectType, $storeCode, $id, $referenceCode, $objects);

    }//end setReference()


    /**
     * Delete reference.
     *
     * @param string $referenceCode The reference code.
     * @param mixed  $objects       Set of objects (User or DataRecord).
     *
     * @return void
     */
    public static function deleteReference(string $objectType, string $id, string $storeCode, string $referenceCode, $objects)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->deleteReference($objectType, $storeCode, $id, $referenceCode, $objects);

    }//end deleteReference()


    /**
     * Returns all user entityids in a specified group.
     *
     * @return array
     */
    public static function getGroupMembers(string $id, string $storeCode)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getGroupMembers($storeCode, $id);

    }//end getGroupMembers()


    /**
     * Sets the name of the user group.
     *
     * @param string $name The name of the group.
     *
     * @return void
     */
    public static function setGroupName(string $id, string $storeCode, $name)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setGroupName($storeCode, $id, $name);
        return true;

    }//end setGroupName()


    /**
     * Set username.
     *
     * @param string $username The username.
     *
     * @return void
     */
    public static function setUsername(string $id, string $storeCode, string $username)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setGroupName($storeCode, $id, $username);
        return true;

    }//end setUsername()


    /**
     * Set first name
     *
     * @param string $firstName The first name of the user.
     *
     * @return void
     */
    public static function setUserFirstName(string $id, string $storeCode, string $firstName)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setGroupName($storeCode, $id, $firstName);
        return true;

    }//end setUserFirstName()


    /**
     * Set last name
     *
     * @param string $lastName The last name of the user.
     *
     * @return void
     */
    public static function setUserLastName(string $id, string $storeCode, string $lastName)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setGroupName($storeCode, $id, $lastName);
        return true;

    }//end setUserLastName()


    /**
     * Returns all parent group entityids for a specified user.
     *
     * @return array
     */
    public static function getUserGroups(string $id, string $storeCode)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getUserGroups($storeCode, $id);

    }//end getUserGroups()


    /**
     * Assign an user to parent groups.
     *
     * @param mixed $groupid Parent user groups to assign the user to.
     *
     * @return void
     */
    public static function addUserToGroup(string $id, string $storeCode, string $groupid)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->addUserToGroup($id, $storeCode, $groupid);

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
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->removeFromGroup($id, $storeCode, $groupid);

    }//end removeUserFromGroup()


    /**
     * Gets the value of the property.
     *
     * @return mixed
     */
    public static function getPropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getPropertyValue($objectType, $storeCode, $id, $propertyCode);

    }//end getPropertyValue()


    /**
     * Sets the value of the property.
     *
     * @param mixed $value The value to set into the property.
     *
     * @return void
     */
    public static function setPropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode, $value)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->setPropertyValue($objectType, $storeCode, $id, $propertyCode, $value);

    }//end setPropertyValue()


    /**
     * Deletes the set value of the property.
     *
     * @return void
     * @throws InvalidDataException Thrown when propertyid is unknown.
     */
    public static function deletePropertyValue(string $objectType, string $storeCode, string $id, string $propertyCode)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $simulatorHandler->deletePropertyValue($objectType, $storeCode, $id, $propertyCode);

    }//end deletePropertyValue()


    /**
     * Returns a flat list of data record's children including their dataRecordid and level.
     *
     * @param integer $depth The max depth.
     *
     * @return array
     */
    public static function getChildren(string $objectType, string $storeCode, string $id, int $depth=null)
    {
        if ($objectType !== 'data') {
            return [];
        }

        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getChildren($objectType, $storeCode, $id, $depth);

    }//end getChildren()


    /**
     * Returns a flat list of data record's parents.
     *
     * @param integer $depth The max depth.
     *
     * @return array
     */
    public static function getParents(string $objectType, string $storeCode, string $id, int $depth=null)
    {
        if ($objectType !== 'data') {
            return [];
        }

        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getParents($objectType, $storeCode, $id, $depth);

    }//end getParents()


    /**
     * Checks if the store exists.
     *
     * @param string $name Name of the store.
     *
     * @return boolean
     */
    public static function getDataStoreExists(string $name)
    {
        return self::storeExists('data', $name);

    }//end getDataStoreExists()


    /**
     * Checks if the store exists.
     *
     * @param string $name Name of the store.
     *
     * @return boolean
     */
    public static function getUserStoreExists(string $name)
    {
        return self::storeExists('user', $name);

    }//end getUserStoreExists()


    /**
     * Checks if the store exists.
     *
     * @param string $type Type of the store, 'data' or 'user'.
     * @param string $name Name of the store.
     *
     * @return boolean
     */
    private static function storeExists(string $type, string $name)
    {
        if (strpos($name, strtolower($GLOBALS['project'])) === 0) {
            $storeJSONPath = Libs\FileSystem::getProjectDir().'/stores.json';
        } else {
            $codeParts     = explode('/', $name);
            $requirement   = $codeParts[0].'/'.$codeParts[1];
            $storeJSONPath = Libs\FileSystem::getRequirementDir($requirement).'/stores.json';
        }

        if (file_exists($storeJSONPath) === false) {
            return false;
        }

        $storeJSON = json_decode(file_get_contents($storeJSONPath), true);
        if (isset($storeJSON['stores']) === true
            && isset($storeJSON['stores'][$type]) === true
            && in_array(basename($name), $storeJSON['stores'][$type]) === true
        ) {
            return true;
        }

        return false;

    }//end storeExists()


    /**
     * Create data record.
     *
     * @param string $type   The data record type code.
     * @param string $parent The ID of the parent data record.
     *
     * @return string
     */
    public static function createDataRecord(string $storeCode, string $customType, string $parent=null)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $dataRecord       = $simulatorHandler->createDataRecord($storeCode, $customType, $parent);

        if ($dataRecord === null) {
            return null;
        }

        return $dataRecord['id'];

    }//end createDataRecord()


    /**
     * Gets the data record type object.
     *
     * @param string $id The ID of the data record.
     *
     * @return null|array
     */
    public static function getDataRecord(string $storeCode, string $id)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $dataRecord       = $simulatorHandler->getDataRecord($storeCode, $id);

        if ($dataRecord === null) {
            return null;
        }

        return [
            'id'        => $dataRecord['id'],
            'typeClass' => $dataRecord['typeClass'],
        ];

    }//end getDataRecord()


    /**
     * Return the data record type object that has the unique property value.
     *
     * The result object should include 'id' & 'typeClass' for dataRecord objectType.
     * The result object should include 'id', 'username', 'firstName' & 'lastName' for user objectType.
     * The result object should include 'id' & 'groupName' for group objectType.
     *
     * @param string $objectType Getting a dataRecord|user|group.
     * @param string $storeCode  The store code.
     * @param string $propertyid The ID of the unique property.
     * @param string $value      The value of the unique property.
     *
     * @return null|array
     * @throws InvalidArgumentException Thrown when value is not valid.
     * @throws InvalidDataException     Thrown when property code is not found.
     * @throws InvalidDataException     Thrown when non-unique property type is used.
     * @throws InvalidDataException     Thrown when store code is not found.
     */
    public static function getObjectInfoByUniquePropertyValue(
        string $objectType,
        string $storeCode,
        string $propertyid,
        string $value
    ) {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $dataRecord       = $simulatorHandler->getDataRecordByValue($storeCode, $propertyid, $value);

        if ($dataRecord === null) {
            return null;
        }

        // TODO: @pete complete this.
        return [
            'id'        => $dataRecord['id'],
            'typeClass' => $dataRecord['typeClass'],
        ];

    }//end getObjectInfoByUniquePropertyValue()


    /**
     * Creates a user and assign it to user groups. Returns created user object.
     *
     * @param string $username  The username of user.
     * @param string $firstName User first name.
     * @param string $lastName  User last name.
     * @param string $type      User type code.
     *                          TODO: this is a palceholder until user types are implemented.
     * @param array  $groups    Optional. Parent user groups to assign the new user to. If left empty, user will be
     *                          created under root user group.
     *
     * @return string
     */
    public static function createUser(string $storeCode, string $username, string $firstName, string $lastName, string $type=null, array $groups=[])
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $user             = $simulatorHandler->createUser($storeCode, $username, $firstName, $lastName, $type, $groups);
        return $user['id'];

    }//end createUser()


    /**
     * Creates a user group and assign it to user groups. Returns created user group object.
     *
     * @param string $groupName The name of user group.
     * @param string $type      User type code.
     *                          TODO: this is a palceholder until user types are implemented.
     * @param array  $groups    Optional. Parent user groups to assign the new user to. If left empty, user will be
     *                          created under root user group.
     *
     * @return string
     */
    public static function createGroup(string $storeCode, string $groupName, string $type=null, array $groups=[])
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $group            = $simulatorHandler->createGroup($storeCode, $groupName, $type, $groups);
        return $group['groupid'];

    }//end createGroup()


    /**
     * Gets a user group if it exists.
     *
     * @param string $storeCode The user stores code the group belongs to.
     * @param string $id        The id of the group.
     *
     * @return array|null
     */
    public static function getGroup(string $storeCode, string $id)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getGroup($storeCode, $id);

    }//end getGroup()


    /**
     * Gets a user from a user store by username.
     *
     * @param string $storeCode The user store's code.
     * @param string $username  The users username.
     *
     * @return array|null
     */
    public static function getUserByUsername(string $storeCode, string $username) {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getUserByUsername($storeCode, $username);

    }//end getUserByUsername()


    /**
     * Gets a user from a user store by userid.
     *
     * @param string $storeCode The user store's code.
     * @param string $id        The users id.
     *
     * @return array|null
     */
    public static function getUser(string $storeCode, string $id) {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        return $simulatorHandler->getUser($storeCode, $id);

    }//end getUser()


    /**
     * Returns the projects instance id.
     *
     * @return string
     * @throws \Exception When fails to create new deployment object.
     */
    public static function getProjectInstanceID()
    {
        return '0.0';

    }//end getProjectInstance()


    /**
     * Login.
     *
     * @param object $user The user we want to login.
     *
     * @return boolean
     */
    public static function login(string $id, string $storeCode)
    {
        $GLOBALS['SIM_SESSION']['user']      = $id;
        $GLOBALS['SIM_SESSION']['userStore'] = $storeCode;
        return true;

    }//end login()


    /**
     * Logout
     *
     * @return void
     */
    public static function logout()
    {
        unset($GLOBALS['SIM_SESSION']['user']);
        unset($GLOBALS['SIM_SESSION']['userStore']);
        return true;

    }//end logout()


    /**
     * Gets Authentication secret key.
     *
     * @return string
     */
    public static function getSecretKey()
    {
        return \PerspectiveSimulator\Authentication::getSecretKey();

    }//end getSecretKey()


    /**
     * Gets the current logged in user and their store.
     *
     * @return array
     */
    public static function getLoggedInUser()
    {
        $userid    = $GLOBALS['SIM_SESSION']['user'];
        $storeCode = $GLOBALS['SIM_SESSION']['userStore'];

        if ($userid === null) {
            return null;
        }

        $user = self::getUser($storeCode, $userid);
        if ($user === null) {
            return null;
        }

        $user['storeCode'] = $storeCode;
        return $user;

    }//end getLoggedInUser()


    /**
     * Sends email.
     *
     * @param string $to      The to address.
     * @param string $from    The from address.
     * @param string $subject The subject of the email.
     * @param string $message The email content.
     *
     * @return void
     */
    public static function sendEmail(string $to, string $from, string $subject, string $message)
    {
        Libs\Email::sendEmail($to, $from, $subject, $message);

    }//end sendEmail()


    /**
     * Returns the full namespaced class name.
     *
     * @param string $objectType The object type, eg. data.
     * @param string $type       The type of the object we are creating.
     *
     * @return string
     */
    public static function getCustomTypeClassByName(string $objectType, string $type)
    {
        if (strpos($type, strtolower($GLOBALS['project'])) === 0) {
            return '\\'.$GLOBALS['projectNamespace'].'CustomTypes\\'.ucfirst($objectType).'\\'.basename($type);
        } else {
            $requirement = explode('/', str_replace(basename($type), '', $type));
            $packageName = str_replace('/'.basename($type), '', $type);
            $requirement = $GLOBALS['projectDependencies'][$packageName];
            return '\\'.$requirement.'CustomTypes\\'.ucfirst($objectType).'\\'.basename($type);
        }

    }//end getCustomTypeClassByName()


    /**
     * Queue job.
     *
     * @param mixed    $queueNames      The queue name(s) to queue this job up with.
     * @param mixed    $jobData         The data for the job that is being queued.
     * @param callable $successCallback An optional callback we will call on successful creation of the job.
     * @param callable $failedCallback  An optional callback we will call on failure to create the job.
     *
     * @return void
     */
    public static function addQueueJob($queueNames, $data, callable $successCallback=null, callable $failedCallback=null)
    {
        \PerspectiveSimulator\Queue\Queue::addJob($queueNames, $data, $successCallback, $failedCallback);

    }//end addQueueJob()


    /**
     * As this is the simulator we will alsways return true here.
     *
     * @return boolean
     */
    public static function isSimulated()
    {
        return true;

    }//end isSimulated()


    /**
     * Returns true if the request is in a read-only mode.
     *
     * @return boolean
     */
    public static function isReadOnly()
    {
        // TODO!

    }//end isReadOnly()


    /**
     * Suppresses 404 response code
     *
     * @return void
     */
    public static function suppress404()
    {
        // TODO!

    }//end suppress404()


    /**
     * Returns the next value of the specified sequence.
     *
     * @param string $sequenceid The name of the sequence. If the sequence name does not match any existing sequences,
     *                           a new sequence will be created.
     *
     * @return string
     */
    public static function getSequenceNextval(string $sequenceid)
    {
        // TODO!

    }//end getSequenceNextval()


    /**
     * Sends a notification to subscriptions.
     *
     * Returns a array of error messages for failed pushes.
     *
     * @param array $subscriptions Array of subscription content.
     * @param string $payload      The payload of message to send.
     * @param array $VAPID         Array of the VAPID settings.
     *
     * @return array
     */
    public static function sendWebPushNotification(array $subscriptions, string $payload='', array $VAPID)
    {
        // TODO!

    }//end sendWebPushNotification()


    /**
     * Get a list of all perspectives that the current user has been placed into.
     *
     * Long Description
     *
     * @param array $limit A list of perspectives to limit the result to. If not specified, all active perspectives will
     *                     be returned.
     *
     * @return array
     */
    public static function getActivePerspectives(array $limit=null)
    {
        // TODO!

    }//end getActivePerspectives()


    /**
     * Checks if the current user is in a list of perspectives.
     *
     * @param array|string $perspectives A list of perspectives to check if the user has been placed into. If only one
     *                                   perspective needs to be checked, a perspective string code can be passed.
     *
     * @return boolean
     */
    public static function isActivePerspective($perspectives)
    {
        // TODO!

    }//end isActivePerspective()


    /**
     * Expands a list of perspective categories into a list of perspective codes.
     *
     * @param array|string $categories A list of category names to expand.
     *
     * @return array
     */
    public static function expandPerspectiveCategory($categories=null)
    {
        // TODO!

    }//end expandPerspectiveCategory()


    /**
     * Returns the active language string.
     *
     * This function is intended to be used from inline PHP situation.
     *
     * @return string
     */
    public static function getActiveLanguage()
    {
        // TODO!

    }//end getActiveLanguage()


    /**
     * Returns true the passed language is the current language.
     *
     * @param string $languageid Languageid to check.
     *
     * @return boolean
     */
    public static function isActiveLanguage($languageid)
    {
        // TODO!

    }//end isActiveLanguage()


    /**
     * Returns the project context of the project.
     *
     * @param string $namespace The namespace of the class trying to get the project context of.
     *
     * @return string
     * @throws InvalidDataException When the project doesn't exist.
     */
    public static function getProjectContext(string $namespace)
    {
        $namespace   .= '\\';
        $namespaceMap = [];
        $namespaceMap[$GLOBALS['project']] = $GLOBALS['projectNamespace'];
        $namespaceMap = array_flip(array_merge($namespaceMap, $GLOBALS['projectDependencies']));

        if (isset($namespaceMap[$namespace]) === true) {
            return $namespaceMap[$namespace];
        }

        throw new \PerspectiveAPI\Exception\InvalidDataException('Project doesn\'t exist.');

    }//end getProjectContext()


    /**
     * Finds if the project exists.
     *
     * @param string $projectCode The namespace of the class trying to get the project context of.
     *
     * @return boolean
     */
    public static function projectExists(string $projectCode)
    {
        $namespaceMap = [];
        $namespaceMap[$GLOBALS['project']] = $GLOBALS['projectNamespace'];
        $namespaceMap = array_merge($namespaceMap, $GLOBALS['projectDependencies']);

       return isset($namespaceMap[$projectCode]);

    }//end projectExists()


    /**
     * Returns list of files that have been autoloaded.
     *
     * @return array
     */
    public static function getAutoloadedFilepaths()
    {
        return Autoload::getAutoloadedFilepaths();

    }//end getAutoloadedFilepaths()


    /**
     * Returns ID of object pending remap to (Redis).
     *
     * @param string $objectType The object type.
     * @param string $storeCode  The store code.
     * @param string $id         The object ID.
     *
     * @return string
     */
    public static function getRemappingid(string $objectType, string $storeCode, string $id)
    {
        $remappingid = null;
        return $remappingid;

    }//end getRemappingid()


    /**
     * Returns ID of object pending remap to (Redis) and finished remapping to (DB).
     *
     * @param string $objectType The object type.
     * @param string $storeCode  The store code.
     * @param string $id         The object ID.
     *
     * @return array
     */
    public static function getRemaps(string $objectType, string $storeCode, string $id)
    {
        $remaps = [
           'remappedid'  => null,
           'remappingid' => null,
        ];

        return $remaps;

    }//end getRemaps()


}//end class
