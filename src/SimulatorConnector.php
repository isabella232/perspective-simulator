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
        $objectType = ucfirst($objectType);
        if (strpos($propertyCode, strtolower($GLOBALS['project'])) === 0) {
            $prop = Libs\FileSystem::getProjectDir().'/Properties/'.$objectType.'/'.basename($propertyCode).'.json';
        } else {
            $codeParts   = explode('/', $propertyCode);
            $requirement = $codeParts[0].'/'.$codeParts[1];
            $prop = Libs\FileSystem::getRequirementDir($requirement).'/Properties/'.$objectType.'/'.basename($propertyCode).'.json';
        }

        if (file_exists($prop) === false) {
            return null;
        }

        $propData = Libs\Util::jsonDecode(file_get_contents($prop));

        return '\PerspectiveAPI\Property\Types\\'.ucfirst($propData['type']);

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
        if (strpos($name, strtolower($GLOBALS['project'])) === 0) {
            $storeDir = Libs\FileSystem::getProjectDir().'/Stores/Data/'.basename($name);
        } else {
            $codeParts   = explode('/', $name);
            $requirement = $codeParts[0].'/'.$codeParts[1];
            $storeDir    = Libs\FileSystem::getRequirementDir($requirement).'/Stores/Data/'.basename($name);
        }

        if (is_dir($storeDir) === true) {
            return true;
        }

        return false;

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
        if (strpos($name, strtolower($GLOBALS['project'])) === 0) {
            $storeDir = Libs\FileSystem::getProjectDir().'/Stores/User/'.basename($name);
        } else {
            $codeParts   = explode('/', $name);
            $requirement = $codeParts[0].'/'.$codeParts[1];
            $storeDir    = Libs\FileSystem::getRequirementDir($requirement).'/Stores/User/'.basename($name);
        }

        if (is_dir($storeDir) === true) {
            return true;
        }

        return false;

    }//end getUserStoreExists()


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
     * @param string $propertyid The ID of the unique property.
     * @param string $value      The value of the unique property.
     *
     * @return null|array
     */
    public static function getDataRecordByValue(string $storeCode, string $propertyid, string $value)
    {
        $simulatorHandler = \PerspectiveSimulator\SimulatorHandler::getSimulator();
        $dataRecord       = $simulatorHandler->getDataRecordByValue($storeCode, $propertyid, $value);

        if ($dataRecord === null) {
            return null;
        }

        return [
            'id'        => $dataRecord['id'],
            'typeClass' => $dataRecord['typeClass'],
        ];

    }//end getDataRecordByValue()


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
            return '\\'.$GLOBALS['projectNamespace'].'\CustomTypes\\'.ucfirst($objectType).'\\'.basename($type);
        } else {
            $requirement = explode('/', str_replace(basename($type), '', $type));
            return '\\'.ucfirst($requirement[0]).'\\'.ucfirst($requirement[1]).'\CustomTypes\\'.ucfirst($objectType).'\\'.basename($type);
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


}//end class
