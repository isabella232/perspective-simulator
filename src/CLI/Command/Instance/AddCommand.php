<?php
/**
 * Project class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Instance;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

use \PerspectiveSimulator\Libs;

/**
 * AddCommand Class
 */
class AddCommand extends \PerspectiveSimulator\CLI\Command\GatewayCommand
{

    protected static $defaultName = 'instance:add';

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
        $this->setDescription('Registers the instance to a Gateway.');
        $this->setHelp('Registers the instance to a Gateway.');
        $this->addArgument('name', InputArgument::REQUIRED);
        $this->addArgument('projectNamespace', InputArgument::REQUIRED);

    }//end configure()


    /**
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $name   = ($input->getArgument('name') ?? null);
        if (empty($input->getArgument('name')) === true) {
            $question = new \Symfony\Component\Console\Question\Question('Please enter an Instance name: ');
            $name     = $helper->ask($input, $output, $question);
            $input->setArgument('name', $name);
        }

        $namespace = ($input->getArgument('projectNamespace') ?? null);
        if (empty($input->getArgument('projectNamespace')) === true) {
            $question   = new \Symfony\Component\Console\Question\Question('Please enter a Project namespace: ');
            $namespace  = $helper->ask($input, $output, $question);
            $input->setArgument('projectNamespace', $namespace);
        }

        $question = sprintf('This will create a new instance "%s" in the project "%s": ', $name, $namespace);
        $confirm  = new \Symfony\Component\Console\Question\ConfirmationQuestion($question, false);
        if ($helper->ask($input, $output, $confirm) === false) {
            exit(1);
        }

    }//end interact()


    /**
     * Validates the namespace of a project.
     *
     * @param string $namespace The namespace string.
     *
     * @return void
     * @throws Exception When namespace is invalid.
     */
    private function validateProjectNamespace(string $namespace)
    {
        if (is_dir($this->storeDir.$namespace) === true) {
            throw new \Exception(sprintf('Duplicate project namespace (%s).', $namespace));
        }

        // Check php namspace.
        $syntaxRes = Libs\Util::checkPHPSyntax('<?php'."\n".'namespace '.$namespace.'; ?'.'>');
        if ($syntaxRes !== true) {
            throw new \Exception(sprintf('Invalid project namespace (%s).', $namespace));
        }

    }//end validateProjectNamespace()


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
        $this->style->title('Creating new instance');
        $response = $this->sendAPIRequest(
            'post',
            '/instance/'.$input->getArgument('projectNamespace'),
            ['name' => $input->getArgument('name')]
        );

        if ($response['curlInfo']['http_code'] === 201) {
            $response['result'] = json_decode($response['result'], true);
            $this->style->success(sprintf('Instance successfully created: %s', $response['result']['instanceid']));
        } else {
            $this->style->error($response['result']);
        }

    }//end execute()


}//end class
