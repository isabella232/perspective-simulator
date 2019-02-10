<?php
/**
 * AddCommand class for Perspective Simulator CLI.
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

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'app:add';

    /**
     * The direcrtory where the export stores the data.
     *
     * @var string
     */
    private $storeDir = null;

    private $baseNamespace = '';

    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Adds a new API specification file.');
        $this->setHelp('Copies a new API specification file to the project.');
        $this->addArgument('type', InputArgument::REQUIRED, 'The type we are adding or deleting, eg: class or directory.');
        $this->addArgument('name', InputArgument::REQUIRED, 'The path to the file or directory (this is realative to the APP folder).');

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

        if (is_dir($this->storeDir) === false) {
            Libs\FileSystem::mkdir($this->storeDir, true);
        }

        $this->type = $input->getArgument('type');

    }//end interact()


    /**
     * Validates the app class or directory name
     *
     * @param string $name The name we are validating.
     *
     * @return string
     * @throws \Exception When name is invalid.
     */
    private function validateName(string $name)
    {
        if ($name === null) {
            $eMsg = sprintf('%s is required.', $this->type);
            throw new \Exception($eMsg);
        }

        $nameParts = explode(DIRECTORY_SEPARATOR, $name);
        if ($this->type === 'directory') {
            $valid = Libs\Util::isValidStringid(end($nameParts));
            if ($valid === false) {
                $eMsg = sprintf('Invalid %s name provided.', $this->type);
                throw new \Exception($eMsg);
            }

            if (is_dir($this->storeDir.$name) === true) {
                throw new \Exception('Duplicate directory name provided.');
            }
        } else {
            $className = str_replace('.php', '', end($nameParts));
            $valid     = Libs\Util::isPHPClassString($className);
            if ($valid === false) {
                $eMsg = sprintf('Invalid %s name provided.', $this->type);
                throw new \Exception($eMsg);
            }

            if (file_exists($this->storeDir.$name) === true) {
                throw new \Exception('Duplicate class name provided.');
            }
        }//end if

        return $name;

    }//end validateName()


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
            $name = $input->getArgument('name');
            $type = $input->getArgument('type');
            $this->validateName($name);
            if ($type === 'directory') {
                Libs\FileSystem::mkdir($this->storeDir.$name, true);
            } else {
                $nameParts = explode(DIRECTORY_SEPARATOR, $name);
                array_pop($nameParts);

                if (count($nameParts) > 0) {
                    $namespace = $this->baseNamespace.'\\'.implode('\\', $nameParts);
                } else {
                    $namespace = $this->baseNamespace;
                }

                $classNameParts = explode(DIRECTORY_SEPARATOR, $name);
                $className      = str_replace('.php', '', end($classNameParts));

                $defaultContent = Libs\Util::getDefaultPHPClass();
                $phpClass       = str_replace(
                    'CLASS_NAME',
                    $className,
                    str_replace(
                        'CLASS_EXTENDS',
                        '',
                        str_replace(
                            'NAMESPACE',
                            $namespace,
                            $defaultContent
                        )
                    )
                );

                $validCode = Libs\Util::checkPHPSyntax($phpClass);
                if ($validCode !== true) {
                    throw new \Exception($validCode);
                }

                $fileName = str_replace('.php', '', implode(DIRECTORY_SEPARATOR, $classNameParts));
                $phpFile  = $this->storeDir.$fileName.'.php';

                $fileDir = $this->storeDir.implode(DIRECTORY_SEPARATOR, $nameParts);
                if (is_dir($fileDir) === false) {
                    // The directory doens't exist so we will attempt to create it too.
                    Libs\FileSystem::mkdir($fileDir, true);
                }

                file_put_contents($phpFile, $phpClass);
            }//end if
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class