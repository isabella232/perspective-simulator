<?php
/**
 * AddCommand class for Perspective Simulator CLI.
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
 * AddCommand Class
 */
class AddReferenceCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:add-reference';

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
        $this->setDescription('Adds a new refernece between stores.');
        $this->setHelp('Adds a new refernece between stores.');
        $this->addOption(
            'targetType',
            null,
            InputOption::VALUE_REQUIRED,
            'The type of store, eg, data or user.',
            null
        );
        $this->addOption(
            'sourceType',
            null,
            InputOption::VALUE_REQUIRED,
            'The type of store, eg, data or user.',
            null
        );

        $this->addOption(
            'targetCode',
            null,
            InputOption::VALUE_REQUIRED,
            'The type of store, eg, data or user.',
            null
        );
        $this->addOption(
            'sourceCode',
            null,
            InputOption::VALUE_OPTIONAL,
            'The type of store, eg, data or user.',
            null
        );

        $this->addOption(
            'targetMultiple',
            null,
            InputOption::VALUE_NONE,
            'Allow multiple of the target.'
        );
        $this->addOption(
            'sourceMultiple',
            null,
            InputOption::VALUE_NONE,
            'Allow multiple of source.'
        );

        $this->addArgument('referenceName', InputArgument::REQUIRED, 'The name of the reference.');

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
        $storeType = $input->getOption('targetType');
        if (empty($input->getOption('targetType')) === true) {
            $question = new \Symfony\Component\Console\Question\ChoiceQuestion(
                'Please select which store type you are wanting to create.',
                ['data', 'user'],
                0
            );

            $storeType = $helper->ask($input, $output, $question);
            $input->setOption('targetType', $storeType);
            $output->writeln('You have just selected: '.$storeType);
        }

        $sourceType = $input->getOption('sourceType');
        if ($sourceType === null){
            $input->setOption('sourceType', $storeType);
        }

        $projectDir = Libs\FileSystem::getProjectDir();
        if (strtolower($storeType) === 'data') {
            $this->type = 'data';
        } else if (strtolower($storeType) === 'user') {
            $this->type = 'user';
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
        $referenceName  = strtolower($input->getArgument('referenceName'));
        $sourceType     = $input->getOption('sourceType');
        $sourceCode     = ($input->getOption('sourceCode') ?? null);
        $sourceMultiple = ($input->getOption('sourceMultiple') ?? false);
        $targetType     = $input->getOption('targetType');
        $targetCode     = $input->getOption('targetCode');
        $targetMultiple = ($input->getOption('targetMultiple') ?? false);

        // Validate the source and target types.
        if ($targetType !== 'user' && $targetType !== 'data') {
            throw new \Exception('Target type must be data or user');
        }
        if ($sourceType !== 'user' && $sourceType !== 'data') {
            throw new \Exception('Source type must be data or user');
        }

        try {
            $this->validateReferenceName($referenceName);
            $reference = [
                'source' => [
                    'type'     => $sourceType,
                    'code'     => $sourceCode,
                    'multiple' => $sourceMultiple,
                ],
                'target' => [
                    'type'     => $targetType,
                    'code'     => $targetCode,
                    'multiple' => $targetMultiple,
                ],
            ];

            $this->stores['references'][$referenceName] = $reference;

            $projectDir = Libs\FileSystem::getProjectDir();
            $stores     = $projectDir.'/stores.json';
            file_put_contents($stores, Libs\Util::jsonEncode($this->stores, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)));

            $this->style->success(sprintf('Reference %s successfully created.', $referenceName));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
