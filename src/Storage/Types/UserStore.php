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

use \PerspectiveSimulator\Bootstrap;
use \PerspectiveSimulator\ObjectType\User;
use \PerspectiveSimulator\Storage\StoreTrait as StoreTrait;

/**
 * User Store Class.
 */
class UserStore
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
     * @param string $code    The name of the user store.
     *
     * @return void
     */
    public function __construct(string $code)
    {
        $this->code = $code;
        $this->type = 'User';

        if (Bootstrap::isWriteEnabled() === true) {
            $storageDir = Bootstrap::getStorageDir();
            $storeDir   = $storageDir.'/'.$code;
            if (is_dir($storeDir) === false) {
                mkdir($storeDir);
            }
        }

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


    /**
     * Gets the records for the store.
     *
     * @return array
     */
    final public function getUsers()
    {
        return $this->records;

    }//end getUsers()


}//end class
