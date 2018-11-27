<?php
/**
 * Router for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator\Requests;

include dirname(__DIR__, 4).'/autoload.php';

$path = $_SERVER['REQUEST_URI'];

if ($path === '/favicon.ico') {
    return;
}

if (isset($_SERVER['QUERY_STRING']) === true) {
    $path = str_replace('?'.$_SERVER['QUERY_STRING'], '', $path);
}

$pathParts = explode('/', $path);
$domain    = array_shift($pathParts);
$type      = array_shift($pathParts);
$project   = ucfirst(array_shift($pathParts));
$path      = implode('/', $pathParts);

if ($project !== null) {
    \PerspectiveSimulator\Bootstrap::load($project);
}

processCORSPreflight();
sendCORSHeaders();

switch ($type) {
    case 'api':
        $queryParams = [];
        parse_str(($_SERVER['QUERY_STRING'] ?? ''), $queryParams);

        $method = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));

        $class = $project.'\APIRouter';
        $class::process($path, $method, $queryParams);
    break;

    case 'cdn':
       \PerspectiveSimulator\Requests\CDN::serveFile($path);
    break;

    default:
        return;
    break;
}


function processCORSPreflight()
{
    $httpMethod = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));
    if ($httpMethod === 'options') {
        if (empty($_SERVER['HTTP_ORIGIN']) === false
            && empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) === false
        ) {
            header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 600');
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
            if (empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) === false) {
                header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            }

            exit();
        }
    }

}//end processCORSPreflight()


function sendCORSHeaders()
{
    if (empty($_SERVER['HTTP_ORIGIN']) === false) {
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
    }

}//end sendCORSHeaders()