<?php
/**
 * FileSystem class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Bootstrap;

/**
 * FileSystem class
 */
class FileSystem
{

    /**
     * The default directory umask.
     *
     * @var integer
     */
    private static $dirMask = 0700;


    /**
     * Create a directory in the file system.
     *
     * @param string  $pathname  The directory path.
     * @param boolean $recursive Default to false.
     *
     * @return boolean
     */
    public static function mkdir(string $pathname, bool $recursive=false)
    {
        $pathname = rtrim($pathname, '/');
        $ret      = mkdir($pathname, self::$dirMask, $recursive);
        return $ret;

    }//end mkdir()


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
        if (is_dir($path) === true) {
            exec('rm -rf '.escapeshellarg($path), $output, $returnVar);
            if ($returnVar === 0) {
                clearstatcache(true, $path);
                $ret = true;
            }
        } else if (file_exists($path) === true) {
            $ret = unlink($path);
            clearstatcache(true, $path);
        }//end if

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
            $ret = true;
        } else if (is_dir($dest) === true) {
            $ret = self::delete($dest);
            $ret = rename($source, $dest);
            clearstatcache(true, $source);
            clearstatcache(true, $dest);
        } else {
            $ret = rename($source, $dest);
            clearstatcache(true, $source);
            clearstatcache(true, $dest);
        }//end if

        return $ret;

    }//end move()


    /**
     * Gets the export directory.
     *
     * @return string
     */
    public static function getExportDir()
    {
        return dirname(__DIR__, 5);

    }//end getExportDir()


    /**
     * Gets the storage directory.
     *
     * @return string
     */
    public static function getSimulatorDir()
    {
        return self::getExportDir().'/simulator';

    }//end getSimulatorDir()


    /**
     * Gets the storage directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getStorageDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        if (Bootstrap::isReadEnabled() === false && Bootstrap::isWriteEnabled() === false) {
            return null;
        }

        return self::getSimulatorDir().'/'.$project.'/storage';

    }//end getStorageDir()


    /**
     * Gets the project directory.
     *
     * @param string $project The project code we are getting the directory for.
     *
     * @return mixed
     */
    public static function getProjectDir(string $project=null)
    {
        if ($project === null) {
            $project = $GLOBALS['project'];
        }

        return self::getExportDir().'/projects/'.$project.'/src';

    }//end getProjectDir()


    /**
     * Lists the contents of a directory.
     *
     * @param string  $path          The path of the directory to list.
     * @param array   $extensions    An array of extensions (with the leading dots)
     *                               of files to return.
     * @param boolean $nested        Include subdirectories in the listing.
     * @param boolean $fullPath      Include the full path, or just the filename.
     * @param string  $fileNameRegEx Reg exp pattern to match the file name against.
     *
     * @return array
     */
    public static function listDirectory(
        string $path,
        array $extensions=[],
        bool $nested=true,
        bool $fullPath=true,
        string $fileNameRegEx=null
    ) {
        $files = [];
        if (file_exists($path) === false) {
            return $files;
        }

        if (empty($extensions) === false) {
            $checkExtension = true;
        } else {
            $checkExtension = false;
        }

        $dir = new \DirectoryIterator($path);
        while ($dir->valid() === true) {
            if ($dir->isDot() === true) {
                // This is '.' or '..'. Not interested.
                $dir->next();
                continue;
            }

            $fileType     = $dir->getType();
            $fullFilename = $dir->getFilename();
            if ($fileType === 'dir' && $nested === true) {
                if ($fullFilename === '.svn') {
                    $dir->next();
                    continue;
                }

                // Recursively call this method to list nested directories.
                $nestedDirectory = self::listDirectory(
                    $dir->getPathName(),
                    $extensions,
                    $nested,
                    $fullPath,
                    $fileNameRegEx
                );

                // Merge the files from the subdirectory with ours.
                $files = array_merge($files, $nestedDirectory);
                $dir->next();
                continue;
            }

            // We have a file, so we need to determine if it fits the specified
            // criteria & skip hidden files.
            if ($fileType === 'file' && $fullFilename{0} !== '.') {
                // Do any file name or extension checking.
                if ($checkExtension === true || $fileNameRegEx !== null) {
                    // Determine the file name and extension.
                    $lastDotPos = strrpos($fullFilename, '.');
                    if ($lastDotPos !== false) {
                        $filename = substr($fullFilename, 0, $lastDotPos);
                        $fileExt  = substr($fullFilename, $lastDotPos);
                    } else {
                        $filename = $fullFilename;
                        $fileExt  = '';
                    }

                    // Test to see if it passes any filename testing.
                    if ($fileNameRegEx !== null
                        && preg_match($fileNameRegEx, $filename) === 0
                    ) {
                        $dir->next();
                        continue;
                    }

                    // Test to see if the file extension passes testing.
                    if ($checkExtension === true
                        && in_array($fileExt, $extensions) === false
                    ) {
                        $dir->next();
                        continue;
                    }
                }//end if

                // File fits our criteria, now we only need to work out
                // which details to add to our array.
                if ($fullPath === true) {
                    $files[] = $dir->getPathName();
                } else {
                    $files[] = $fullFilename;
                }
            }//end if

            $dir->next();
        }//end while

        sort($files);

        return $files;

    }//end listDirectory()


}//end class
