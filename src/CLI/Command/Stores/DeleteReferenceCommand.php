<?php
/**
 * DeleteReferenceCommand class for Perspective Simulator CLI.
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
 * DeleteReferenceCommand Class
 */
class DeleteReferenceCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    protected static $defaultName = 'storage:delete-reference';

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
        $this->setDescription('Deletes a refernece.');
        $this->setHelp('Deletes a refernece.');
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

        $helper  = $this->getHelper('question');
        $confirm = new \Symfony\Component\Console\Question\ConfirmationQuestion(
            'This will delete the reference "'.$input->getArgument('referenceName').'" (y/N):',
            false
        );
        if ($helper->ask($input, $output, $confirm) === false) {
            return;
        }

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
     * Executes the create new project command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $referenceName = $input->getArgument('referenceName');

        try {
            if (in_array($referenceName, array_keys($this->stores['references'])) === false) {
                throw new \Exception(sprintf('%s doesn\'t exist.', $referneceName));
            }
            unset($this->stores['references'][$referenceName]);

            $projectDir = Libs\FileSystem::getProjectDir();
            $stores     = $projectDir.'/stores.json';
            file_put_contents($stores, Libs\Util::jsonEncode($this->stores, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)));

            $this->style->success(sprintf('Refernece %1$s successfully deleted.', $referenceName));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }//end try

    }//end execute()


}//end class
