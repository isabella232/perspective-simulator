<?php
/**
 * DiffCommand class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command\Deployment;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use \PerspectiveSimulator\Libs;

/**
 * DiffCommand Class
 */
class DiffCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Commands name.
     *
     * @var string
     */
    protected static $defaultName = 'deployment:diff';

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
        $this->setDescription('Detects changes between versions.');
        $this->setHelp('Detectst the changes between different versions of projects.');
        $this->addArgument('from', InputArgument::REQUIRED, 'The commit hash or tag name to get the changes from.');
        $this->addArgument(
            'to',
            InputArgument::OPTIONAL,
            'The commit hash or tag name to get the changes to, if not provided will use current HEAD.'
        );

    }//end configure()


    /**
     * Executes the create new project command.
     *
     * @param InputInterface  $input  Console input object.
     * @param OutputInterface $output Console output object.
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $to   = ($input->getArgument('to') ?? null);
        $diff = Libs\Git::getDiff($from, $to);

        $changes = $this->parseDiff($input, $diff);

        foreach ($changes as $project => $changeData) {
            $this->style->title($project);
            foreach ($changeData as $type => $change) {
                $gitType = '';
                switch ($type) {
                    case 'A':
                        $gitType = 'Added';
                    break;

                    case 'C':
                        $gitType = 'Copied';
                    break;

                    case 'D':
                        $gitType = 'Deleted';
                    break;

                    case 'M':
                        $gitType = 'Modified';
                    break;

                    case 'R':
                        $gitType = 'Renamed';
                    break;

                    case 'T':
                        $gitType = 'Type Change';
                    break;

                    case 'U':
                        $gitType = 'Unmerged';
                    break;

                    case 'X':
                        $gitType = 'Unknown';
                    break;

                    case 'B':
                        $gitType = 'Pairing Broken';
                    break;

                    default:
                        $gitType = '';
                    break;
                }//end switch

                $this->style->section('Following changes detected as: '.$gitType);

                foreach ($change as $system => $paths) {
                    if ($system === 'Stores') {
                        foreach ($paths as $store => $storePaths) {
                            $this->style->block($store.' Stores', null, 'fg=yellow', ' ! ');
                            $this->style->listing($storePaths);
                        }
                    } else {
                        $this->style->block($system, null, 'fg=yellow', ' ! ');
                        $this->style->listing($paths);
                    }
                }
            }//end foreach
        }//end foreach

    }//end execute()


}//end class
