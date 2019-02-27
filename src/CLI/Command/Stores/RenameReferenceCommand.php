<?php
/**
 * RenameReferenceCommand class for Perspective Simulator CLI.
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
 * RenameReferenceCommand Class
 */
class RenameReferenceCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:rename-reference';

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
        $this->setDescription('Renames a refernece.');
        $this->setHelp('Renames a refernece.');

        $this->addArgument('referenceName', InputArgument::REQUIRED, 'The name of the reference.');
        $this->addArgument('newReferenceName', InputArgument::REQUIRED, 'The new name of the reference.');

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

        $projectDir = Libs\FileSystem::getProjectDir();
        $stores     = $projectDir.'/stores.json';
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
     * Validates the name of the reference.
     *
     * @param string $name Name of the data store.
     *
     * @return boolean
     * @throws \Exception When name is invalid.
     */
    private function validateReferenceName(string $name)
    {
        if ($name === null) {
            throw new \Exception('Reference name is required.');
        }

        $valid = Libs\Util::isValidStringid($name);
        if ($valid === false) {
            throw new \Exception('Invalid reference name provided');
        }

        if (in_array($name, array_keys($this->stores['references'])) === true) {
            throw new \Exception('Reference name is already in use');
        }

        return true;

    }//end validateReferenceName()


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
        $referenceName    = strtolower($input->getArgument('referenceName'));
        $newReferenceName = strtolower($input->getArgument('newReferenceName'));

        try {
            if (in_array($referenceName, array_keys($this->stores['references'])) === false) {
                throw new \Exception(sprintf('%s doesn\'t exist.', $referenceName));
            }

            $this->validateReferenceName($newReferenceName);

            $this->stores['references'][$newReferenceName] = $this->stores['references'][$referenceName];
            unset($this->stores['references'][$referenceName]);

            $this->logChange(
                'rename',
                lcfirst($this->type).'Reference',
                [
                    'from' => $referenceName,
                    'to'   => $newReferenceName,
                ]
            );

            $projectDir = Libs\FileSystem::getProjectDir();
            $stores     = $projectDir.'/stores.json';
            file_put_contents($stores, Libs\Util::jsonEncode($this->stores, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)));

            $this->style->success(
                sprintf(
                    'Refernece %1$s successfully renamed to %2$s.',
                    $referenceName,
                    $newReferenceName
                )
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
