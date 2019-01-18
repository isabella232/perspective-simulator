<?php
/**
 * Authentication class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

use PerspectiveSimulator\Libs;

/**
 * Authentication class.
 */
class Authentication
{

    /**
     * Secret Key.
     *
     * @var string
     */
    private static $secretKey = null;


    /**
     * Generates the secret key.
     *
     * @return string
     */
    public static function generateSecretKey()
    {
        $simulatorDir = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir();

        // Check if the key exists in file and return that instead or generating a new one.
        $authFile = $simulatorDir.'/'.$GLOBALS['projectPath'].'/authentication.json';
        if (Bootstrap::isReadEnabled() === true && file_exists($authFile) === true) {
            $keys            = Libs\Util::jsonDecode(file_get_contents($authFile));
            self::$secretKey = $keys['secretKey'];
            return self::$secretKey;
        }

        $uid        = strtoupper(md5(uniqid(random_int(0, 2147483647), true)));
        $projectKey = substr($uid, 0, 32);

        if (Bootstrap::isWriteEnabled() === true) {
            $projectDir = $simulatorDir.'/'.$GLOBALS['projectPath'];
            if (is_dir($projectDir) === false) {
                mkdir($projectDir);
            }

            file_put_contents(
                $projectDir.'/authentication.json',
                Libs\Util::jsonEncode(['secretKey' => $projectKey])
            );
        }

        self::$secretKey = $projectKey;

        return $projectKey;

    }//end generateSecretKey()


    /**
     * Gets the secret key.
     *
     * @return string | null
     */
    public static function getSecretKey()
    {
        if (self::$secretKey === null) {
            if (Bootstrap::isReadEnabled() === true ) {
                $simulatorDir = \PerspectiveSimulator\Libs\FileSystem::getSimulatorDir();
                $authFile     = $simulatorDir.'/'.$GLOBALS['projectPath'].'/authentication.json';
                if (file_exists($authFile) === true) {
                    $keys            = Libs\Util::jsonDecode(file_get_contents($authFile));
                    self::$secretKey = $keys['secretKey'];
                    return self::$secretKey;
                }
            }

            return null;
        }

        return self::$secretKey;

    }//end getSecretKey()


}//end class
