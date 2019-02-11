<?php
/**
 * Git class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Bootstrap;

/**
 * Git class
 */
class Git
{


    /**
     * Removes file or directory.
     *
     * @param string $path Path to file or directory.
     *
     * @return booleans
     */
    public static function delete(string $path)
    {
        if ($path === '/') {
            // We don't want to delete / so just return here.
            return false;
        }

        $ret = false;
        if (is_dir($path) === true || file_exists($path) === true) {
            exec('git rm -rf '.escapeshellarg($path), $output, $returnVar);
            if ($returnVar === 0) {
                $ret = true;
            }
        }

        return $ret;

    }//end delete()


    /**
     * Moves a file or directory.
     *
     * @param string $source The source file or directory we are wanting to move/rename.
     * @param string $dest   The destination after the move/rename.
     *
     * @return boolean
     */
    public static function move(string $source, string $dest)
    {
        $ret = false;
        if ($source === $dest) {
            return true;
        } else if (is_dir($dest) === true) {
            FlieSystem::delete($dest);
        }

        exec('git mv '.escapeshellarg($source).' '.escapeshellarg($dest), $output, $returnVar);
        if ($returnVar === 0) {
            $ret = true;
        }

        return $ret;

    }//end move()


    /**
     * Gets a diff summary between 2 commits or tags.
     *
     * @param string      $from The commit hash or tag name to get the changes from.
     * @param string|null $to   The commit hash or tag name to get the changes to, if not provided will use current HEAD.
     *
     * @return array
     */
    public static function getDiff(string $from, string $to=null)
    {
        $cmd = 'git diff --no-renames --name-status '.$from;
        if ($to !== null) {
            $cmd .= '...'.$to;
        }

        $diff      = shell_exec($cmd);
        $diffArray = explode("\n", $diff);
        array_pop($diffArray);

        return $diffArray;

    }//end getDiff()


}//end class
