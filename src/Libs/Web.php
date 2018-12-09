<?php
/**
 * Web class for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Libs;

use PerspectiveSimulator\Bootstrap;

/**
 * Web class
 */
class Web
{


    /**
     * Send a content type header.
     *
     * @param string $mimeType The mime type to send.
     * @param string $charset  The charset of the page.
     *
     * @return void
     */
    public static function sendContentTypeHeader(string $mimeType='text/html', string $charset=null)
    {
        $mineStr = $mimeType;
        if ($charset !== null) {
            $mineStr .= '; charset='.$charset;
        }

        $headers = ['Content-Type' => $mineStr];

        if ($mimeType !== 'application/octet-stream') {
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        self::headers($headers);

    }//end sendContentTypeHeader()


    /**
     * Send HTTP headers.
     *
     * It also performs unit test clean up.
     *
     * @param array $headers List of key and value pairs.
     *
     * @return void
     */
    public static function headers(array $headers)
    {
        if (headers_sent() === false) {
            foreach ($headers as $header => $value) {
                if ($header === 'http') {
                    header($value);
                } else {
                    header($header.': '.$value);
                }
            }
        }

    }//end headers()


    /**
     * Function to send 404.
     *
     * @return void
     */
    public static function send404()
    {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo 'Page not found';
        exit();

    }//end send404()


    /**
     * Turns a string into a valid web path.
     *
     * @param string $path String to fix.
     *
     * @return string
     */
    public static function makeValidWebPathString($path)
    {
        $path = strtolower($path);
        $path = preg_replace('/&[^;]+;/', '', $path);
        $path = preg_replace('/[^a-z0-9\\s\-\.]/', '', $path);
        $path = preg_replace('/[\\s-]+/', '-', $path);
        $path = trim($path, '-');
        return $path;

    }//end makeValidWebPathString()


}//end class
