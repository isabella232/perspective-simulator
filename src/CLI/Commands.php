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
    $commands = [
        '\\PerspectiveSimulator\\CLI\\Command\\API\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\API\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\API\\UpdateCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\APP\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\APP\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\CDN\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\CDN\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\CustomTypes\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\CustomTypes\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Project\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Project\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Property\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Property\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Property\\RenameCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Queue\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Queue\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Queue\\RenameCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Simulator\\CleanCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Simulator\\InstallCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Simulator\\ServerCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Stores\\AddCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Stores\\AddReferenceCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Stores\\DeleteCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Stores\\DeleteReferenceCommand',
        '\\PerspectiveSimulator\\CLI\\Command\\Stores\\RenameCommand',
    ];

    $runner->registerCommands($commands);
}
