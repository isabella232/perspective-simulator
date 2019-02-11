<?php
/**
 * RenameCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\APP;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * RenameCommand Class
 */
class RenameCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'app:rename';

    /**
     * The direcrtory where the export stores the data.
     *
     * @var string
     */
    private $storeDir = null;

    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Deletes an App class or directory.');
        $this->setHelp('Deletes an App class or directory.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'The type we are deleting, eg: class or directory.',
            null
        );
        $this->addArgument('name', InputArgument::REQUIRED, 'The path to the file or directory (this is realative to the APP folder).');
        $this->addArgument('newName', InputArgument::REQUIRED, 'The new path to the file or directory (this is realative to the APP folder).');

    }//end configure()


    /**
     * Make sure that the system name is set.
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inProject($input, $output);

        $projectDir          = Libs\FileSystem::getProjectDir();
        $this->storeDir      = $projectDir.'/App/';
        $this->baseNamespace = $GLOBALS['projectNamespace'].'\\App';

    }//end interact()


    /**
     * Executes the create new project command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $name    = $input->getArgument('name');
            $newName = $input->getArgument('newName');
            $type    = $input->getOption('type');
            if ($type === 'directory') {
                $path = $this->storeDir.$name;
                if (is_dir($path) === false) {
                    $eMsg = sprintf('The directory "%s" doesn\'t exist.', $path);
                    throw new \Exception($eMsg);
                }

                $newPath = $this->storeDir.$newName;
                if (is_dir($newPath) === false) {
                    $eMsg = sprintf('The directory "%s" doesn\'t exist.', $newPath);
                    throw new \Exception($eMsg);
                }
            } else {
                // Remove .php incase it was provided we will readd to ensure its there.
                $path = str_replace('.php', '', $this->storeDir.$name);
                $path = $path.'.php';

                if (file_exists($path) === false) {
                    $eMsg = sprintf('App class "%s" doesn\'t exist.', $path);
                    throw new \Exception($eMsg);
                }

                $newPath = str_replace('.php', '', $this->storeDir.$newName);
                $newPath = $newPath.'.php';
                if (file_exists($path) === false) {
                    $eMsg = sprintf('App class "%s" doesn\'t exist.', $newPath);
                    throw new \Exception($eMsg);
                }

                // PHP file.
                $classContent = file_get_contents($path);
                $phpClass     = str_replace(
                    'class '.$name,
                    'class '.$newName,
                    $classContent
                );
                file_put_contents($path, $phpClass);
            }//end if



            Libs\Git::move($path, $newPath);

            $this->logChange(
                'rename',
                'App',
                [
                    'from' => $name,
                    'to'   => $newName,
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class