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
        $headers = ['X-Sim-Key: '.$this->gateway->getGatewayKey()];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gateway->getGatewayURL().$uri);
        switch (strtolower($method)) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($msg));
            break;

            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($msg));
            break;

            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

            case 'head':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($ch, CURLOPT_NOBODY, true);
            break;
        }//end switch

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        // TODO: SSL Verification needed for later!
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if (empty($options) === false) {
            foreach ($options as $name => $value) {
                curl_setopt($ch, $name, $value);
            }
        }

        $success = curl_exec($ch);
        if ($success === false) {
            $errMsg = 'cURL failed: '.curl_error($ch);
            throw new \Exception($errMsg);
        }

        $curlinfo = curl_getinfo($ch);
        $result   = [
            'result'   => $success,
            'curlInfo' => $curlinfo,
        ];
        curl_close($ch);
        return $result;

    }//end sendAPIRequest()


}//end class
