<?php
/**
 * Perspective command for the perspective simulator
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

include_once $proot.'/vendor/autoload.php';
if (isset($runner) === true && empty($runner) === false) {
    $commands = [];
    $dirs     = glob(dirname(__FILE__).'/Command/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $group         = basename($dir);
        $groupCommands = scandir($dir);
        foreach ($groupCommands as $command) {
            if ($command[0] === '.'
                || substr($command, -4) !== '.php'
            ) {
                continue;
            }

            $commands[] = '\\PerspectiveSimulator\\CLI\\Command\\'.$group.'\\'.str_replace('.php', '', $command);
        }
    }

    $runner->registerCommands($commands);
}
