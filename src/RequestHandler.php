<?php
/**
 * Request Handler class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */
namespace PerspectiveSimulator;

use \PerspectiveSimulator\Libs;

/**
 * RequestHandler Class
 */
class RequestHandler
{

    /**
     * The method.
     *
     * @var string
     */
    private $_method = null;

    /**
     * The url.
     *
     * @var string
     */
    private $_url = null;

    /**
     * The data.
     *
     * @var array
     */
    private $_data = [];

    /**
     * Extra headers.
     *
     * @var array
     */
    private $_headers = [];

    /**
     * Curl object.
     *
     * @var object
     */
    private $_ch = null;

    /**
     * Response.
     *
     * @var mixed
     */
    private $_response = null;

    /**
     * Info.
     *
     * @var mixed
     */
    private $_info = null;

    /**
     * Error message.
     *
     * @var mixed
     */
    private $_error = null;


    /**
     * Valid method.
     *
     * @var array
     */
    private $_validMethods = [
        'get',
        'post',
        'put',
        'delete',
        'head',
        'options',
    ];


    /**
     * Build a request handler.
     *
     * @return object
     */
    public function __construct()
    {
        // Initialise properties.
        $this->_method   = 'post';
        $this->_url      = null;
        $this->_data     = [];
        $this->_headers  = [];
        $this->_options  = [];
        $this->_response = null;
        $this->_info     = null;
        $this->_error    = null;

        return $this;

    }//end constructor


    /**
     * Execute the request.
     *
     * @return object
     * @throws Exception If the url was not set.
     */
    public function execute()
    {
        $this->_ch = curl_init();

        if ($this->_url === null) {
            throw new \Exception('No url set, not running');
        } else {
            curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
        }//end if

        switch ($this->_method) {
            case 'post':
                curl_setopt($this->_ch, CURLOPT_POST, 1);
                curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $this->_data);
            break;

            case 'put':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $this->_data);
            break;

            case 'delete':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

            case 'head':
                curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($this->_ch, CURLOPT_NOBODY, true);
            break;
        }//end switch

        if (empty($this->_headers) === false) {
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
        }//end if

        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->_ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 20);

        // HTTPS Handling.
        if (strpos($this->_url, 'https://') === 0) {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, true);

            // Self signed certificate handling.
            $certDir = Libs\FileSystem::getSimulatorDir().'/certs';
            if (Libs\FileSystem::isDirEmpty($certDir) === false) {
                curl_setopt($this->_ch, CURLOPT_CAPATH, $certDir);
            }//end if
        }//end if

        if (empty($this->_options) === false) {
            foreach ($this->_options as $name => $value) {
                curl_setopt($this->_ch, $name, $value);
            }
        }//end if

        $this->_response = curl_exec($this->_ch);
        if ($this->_response === false) {
            $this->_error = curl_error($this->_ch);
        } else {
            $this->_error = null;
            $this->_info  = curl_getinfo($this->_ch);
        }//end if

        curl_close($this->_ch);
        $this->_ch = null;

        return $this;

    }//end execute()


    /**
     * Return the result.
     *
     * @return array
     * @throws Exception When execute has not been called first.
     */
    public function getResult()
    {
        if ($this->_response === null) {
            throw new \Exception('No response, please run execute first');
        } else {
            if ($this->_response === false) {
                $result = [
                    'result' => false,
                    'error'  => $this->_error,
                ];
            } else {
                $result = [
                    'result'   => $this->_response,
                    'curlInfo' => $this->_info,
                ];
            }//end if
        }//end if

        return $result;

    }//end getResult()


    /**
     * Set the POST data.
     *
     * @param array $data The data.
     *
     * @return object
     */
    public function setData(array $data)
    {
        $this->_data = $data;

        return $this;

    }//end setData()


    /**
     * Set any extra headers.
     *
     * @param array $headers The headers.
     *
     * @return object
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = $headers;

        return $this;

    }//end setHeaders()


    /**
     * Set a method.
     *
     * @param string $method The method to use.
     *
     * @return object
     * @throws Exception On invalid method.
     */
    public function setMethod(string $method)
    {
        $method = strtolower($method);
        if (in_array($method, $this->_validMethods) === true) {
            $this->_method = $method;
        } else {
            throw new \Exception(sprintf('Invalid method %s', $method));
        }//end if

        return $this;

    }//end setMethod()


    /**
     * Set curl options.
     *
     * @param array $options The curl options.
     *
     * @return object
     */
    public function setOptions(array $options)
    {
        $filtered = [];
        foreach ($options as $name => $value) {
            $filtered[$name] = $value;
        }//end foreach

        $this->_options = $filtered;

        return $this;

    }//end setOptions()


    /**
     * Set the url.
     *
     * @param string $url The url to request.
     *
     * @return object
     */
    public function setURL(string $url)
    {
        $this->_url = $url;

        return $this;

    }//end setURL()


}//end class
