<?php
/**
 * Command class for Perspective Simulator CLI.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\CLI\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use \PerspectiveSimulator\Libs;
use \PerspectiveSimulator\RequestHandler;

/**
 * Command Class
 */
class GatewayCommand extends \PerspectiveSimulator\CLI\Command\Command
{

    /**
     * Cache of the gateway object.
     *
     * @var object
     */
    protected $gateway = null;


    /**
     * Initialize's some components for us to use.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        // Prepare Gateway object.
        $this->gateway = new \PerspectiveSimulator\Gateway();

    }//end initialize()


    /**
     * Send POST API request to the destinated system.
     *
     * It sends API request via POST to the destined system.
     *
     * @param string $url     URL of the system to send the request.
     * @param array  $msg     Array of messages to send.
     * @param array  $options Extra curl options.
     *
     * @return array
     * @throws ChannelException Error occurred.
     */
    public function sendAPIRequest(string $method, string $uri, array $msg=[], array $options=[])
    {
        $headers  = ['X-Sim-Key: '.$this->gateway->getGatewayKey()];
        $url      = $this->gateway->getGatewayURL().$uri;
        $request  = new RequestHandler();
        $response = $request->setMethod($method)
            ->setURL($url)
            ->setData($msg)
            ->setOptions($options)
            ->setHeaders($headers)
            ->execute()
            ->getResult();
        if ($response['result'] === false) {
            $errMsg = 'cURL failed: '.$response['error'];
            throw new \Exception($errMsg);
        }

        return $response;

    }//end sendAPIRequest()


}//end class
