<?php
/**
 * UserStore class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\StorageType;

require_once dirname(__FILE__, 2).'/StoreTrait.inc';

use \PerspectiveAPI\Storage\Types\UserStore as PerspectiveAPIUserStore;
use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\Libs;
use \Perspective\PHPClass\ObjectType\User;
use \PerspectiveSimulator\Storage\StoreTrait as StoreTrait;

/**
 * User Store Class.
 */
class UserStore extends PerspectiveAPIUserStore
{

    use StoreTrait;

    /**
     * Map of usernames to user objects.
     *
     * @var array
     */
    private $usernameMap = [];


    /**
     * Constructs a new user store.
     *
     * @param string $code The name of the user store.
     *
     * @return void
     */
    public function __construct(string $code)
    {
        parent::__construct($code);
        $this->type = 'User';

        if (Bootstrap::isWriteEnabled() === true) {
            $storageDir = \PerspectiveSimulator\Libs\FileSystem::getStorageDir();
            $storeDir   = $storageDir.'/'.$code;
            if (is_dir($storeDir) === false) {
                \PerspectiveSimulator\Libs\FileSystem::mkdir($storeDir);
            }
        }

        $this->load();

    }//end __construct()


    /**
     * Creates a new user record in the user store.
     *
     * @param string $username  The username to give to the user.
     * @param string $firstName The first name of the user.
     * @param string $lastName  The last name of the user.
     * @param string $type      Currently unused.
     * @param array  $groups    An array of user groups if empty user will be in top level.
     *
     * @return object|null
     */
    final public function createUser(
        string $username,
        string $firstName,
        string $lastName,
        string $type=null,
        array $groups=[]
    ) {
        $recordid = ($this->numRecords++).'.1';
        $record   = new User($this, $recordid, $username, $firstName, $lastName);

        $this->records[$recordid] = ['object' => $record];

        $this->usernameMap[$username] = $record;

        \PerspectiveSimulator\Bootstrap::queueSave($this);

        return $record;

    }//end createUser()


    /**
     * Gets a user if they exist by username.
     *
     * @param string $username The username of the user.
     *
     * @return object|null
     */
    final public function getUserByUsername(string $username)
    {
        if (isset($this->usernameMap[$username]) === false) {
            return null;
        }

        return $this->usernameMap[$username];

    }//end getUserByUsername()


    /**
     * Gets a user if they exist by userid.
     *
     * @param string $userid The userid of the user.
     *
     * @return object|null
     */
    final public function getUser(string $userid)
    {
        if (isset($this->records[$userid]) === false) {
            return null;
        }

        return $this->records[$userid]['object'];

    }//end getUser()


    final public function getGroup(string $groupid)
    {
        // TODO:

    }//end getGroup()


    /**
     * Gets the records for the store.
     *
     * @return array
     */
    final public function getUsers()
    {
        return $this->records;

    }//end getUsers()


    /**
     * Save the store as a json object.
     *
     * @return boolean
     */
    final public function save()
    {
        if (Bootstrap::isWriteEnabled() === false) {
            return false;
        }

        $store = [
            'records'     => [],
            'uniqueMap'   => [],
            'usernameMap' => [],
        ];

        foreach ($this->usernameMap as $username => $record) {
            $store['usernameMap'][$username] = $record->getId();
        }

        foreach ($this->records as $recordid => $data) {
            $children = [];
            if (isset($data['children']) === true) {
                $children = (array_keys($data['children']) ?? []);
            }

            $store['records'][$recordid] = [
                'depth'    => ($data['depth'] ?? null),
                'children' => $children,
                'parent'   => ($data['parent'] ?? null),
            ];
        }

        foreach ($this->uniqueMap as $propid => $values) {
            $store['uniqueMap'][$propid] = [];
            foreach ($values as $value => $record) {
                $store['uniqueMap'][$propid][$value] = $record->getId();
            }
        }

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';
        file_put_contents($filePath, Libs\Util::jsonEncode($store));
        return true;

    }//end save()


    /**
     * Loads the Stores cache.
     *
     * @return boolean
     */
    public function load()
    {
        if (Bootstrap::isReadEnabled() === false) {
            return false;
        }

        $storageDir = Libs\FileSystem::getStorageDir();
        $filePath   = $storageDir.'/'.$this->code.'/store.json';

        if (is_file($filePath) === false) {
            return false;
        }

        $store  = Libs\Util::jsonDecode(file_get_contents($filePath));
        $prefix = Bootstrap::generatePrefix($GLOBALS['project']);

        foreach ($store['records'] as $recordid => $data) {
            $recordPath = dirname($filePath).'/'.$recordid.'.json';
            $recordData = Libs\Util::jsonDecode(file_get_contents($recordPath));
            $type       = $recordData['type'];
            $data['object'] = new $type(
                $this,
                $recordid,
                $recordData['username'],
                $recordData['properties'][$prefix.'-__first-name__'],
                $recordData['properties'][$prefix.'-__last-name__']
            );

            $this->records[$recordid] = $data;

            $baseRecordid     = (int) substr($recordid, 0, -2);
            $this->numRecords = max($this->numRecords, $baseRecordid);
        }//end foreach

        foreach ($store['records'] as $recordid => $data) {
            $children = [];
            foreach ($data['children'] as $childid) {
                $children[$childid] = $this->records[$childid]['object'];
            }

            $this->records[$recordid]['children'] = $children;
            $this->records[$recordid]['parent']   = $data['parent'];
        }

        foreach ($store['uniqueMap'] as $propid => $values) {
            $this->uniqueMap[$propid] = [];
            foreach ($values as $value => $recordid) {
                $this->uniqueMap[$propid][$value] = $this->records[$recordid]['object'];
            }
        }

        $this->numRecords++;

        foreach ($store['usernameMap'] as $username => $recordid) {
            $this->usernameMap[$username] = $this->records[$recordid]['object'];
        }

        return true;

    }//end load()


}//end class
