<?php
/**
 * RenameCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Stores;

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

    protected static $defaultName = 'storage:rename';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $type = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $readableType = '';

    /**
     * Readable type for command object.
     *
     * @var string
     */
    private $storeDir = '';


    /**
     * Configures the init command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Renames store in a project.');
        $this->setHelp('Renames store in a project.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'The type of the new store, eg, data or user',
            null
        );
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the store being renamed.');
        $this->addArgument('newName', InputArgument::REQUIRED, 'The new name of the store.');

    }//end configure()


    /**
     * Make sure that the system name is set.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inProject($input, $output);

        $helper    = $this->getHelper('question');
        $storeType = $input->getOption('type');
        if (empty($input->getOption('type')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which store type you are wanting to create.',
                ['data', 'user'],
                0
            );

            $storeType = $helper->ask($input, $output, $question);
            $input->setOption('type', $storeType);
            $output->writeln('You have just selected: '.$storeType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($storeType) === 'data') {
            $this->readableType = 'Data Store';
            $this->type         = 'data';
        } else if (strtolower($storeType) === 'user') {
            $this->readableType = 'User Store';
            $this->type         = 'user';
        }

        $stores = $projectDir.'/stores.json';
        if (file_exists($stores) === false) {
            file_put_contents(
                $stores,
                Libs\Util::jsonEncode(
                    [
                        'stores' => [
                            'data' => [],
                            'user' => [],
                        ],
                        'references' => [],
                    ],
                    (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                )
            );
        }

        $this->stores = Libs\Util::jsonDecode(file_get_contents($stores));

    }//end interact()


    /**
     * Validates the name of the store.
     *
     * @param string $name Name of the data store.
     *
     * @return boolean
     * @throws \Exception When name is invalid.
     */
    private function validateStoreName(string $name)
    {
        if ($name === null) {
            $eMsg = sprintf('%s name is required.', $this->readableType);
            throw new \Exception($eMsg);
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            $eMsg = sprintf('Invalid %s name provided', $this->readableType);
            throw new \Exception($eMsg);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        $dirs       = glob($this->storeDir.'*', GLOB_ONLYDIR);

        if (in_array($name, $this->stores['stores'][$this->type]) === true) {
            $eMsg = sprintf('%s name is already in use', $this->readableType);
            throw new \Exception($eMsg);
        }

        return true;

    }//end validateStoreName()


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
            $oldName = strtolower($input->getArgument('name'));
            $newName = strtolower($input->getArgument('newName'));
            $this->validateStoreName($newName);

            $this->stores['stores'][$this->type]   = array_diff($this->stores['stores'][$this->type], [$oldName]);
            $this->stores['stores'][$this->type][] = $newName;

            $projectDir = Libs\FileSystem::getProjectDir();
            $stores     = $projectDir.'/stores.json';
            file_put_contents(
                $stores,
                Libs\Util::jsonEncode(
                    $this->stores,
                    (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                )
            );

            $this->logChange(
                'rename',
                $this->type,
                [
                    'from' => $oldName,
                    'to'   => $newName,
                ]
            );

            $this->style->success(
                sprintf(
                    '%1$s %2$s successfully renamed to %3$s.',
                    $this->readableType,
                    $oldName,
                    $newName
                )
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
