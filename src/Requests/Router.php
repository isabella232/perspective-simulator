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

ini_set('error_log', dirname(__DIR__, 5).'/simulator/error_log');

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

if ($type !== 'admin') {
    $v1 = array_shift($pathParts);
    $v2 = array_shift($pathParts);

    $installedProjects = \PerspectiveSimulator\Libs\Util::jsonDecode(
        file_get_contents(\PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/projects.json')
    );

    $vendor = null;
    if (isset($installedProjects[strtolower($v1.'/'.$v2)]) === true) {
        $vendor = $v1.'\\'.$v2;
    } else if (isset($installedProjects[strtolower($v1)]) === true) {
        $vendor    = $v1;
        $pathParts = array_merge([$v2], $pathParts);
    }

    if ($vendor !== null) {
        \PerspectiveSimulator\Bootstrap::load($vendor);
    }
}

$path = implode('/', $pathParts);

processCORSPreflight();
sendCORSHeaders();

switch ($type) {
    case 'api':
        $queryParams = [];
        parse_str(($_SERVER['QUERY_STRING'] ?? ''), $queryParams);

        $method = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));

        $class = $GLOBALS['projectNamespace'].'\APIRouter';
        $class::process($path, $method, $queryParams);
    break;

    case 'cdn':
       \PerspectiveSimulator\Requests\CDN::serveFile($path);
    break;

    case 'web':
        $method = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));
        $class  = $GLOBALS['projectNamespace'].'\ViewRouter';
        $class::process('/'.$path, strtoupper($method));
    break;

    case 'admin':
        \PerspectiveSimulator\Requests\UI::paint($path);
    break;

    case 'tests':
        $dir = \PerspectiveSimulator\Libs\FileSystem::getExportDir().'/projects/'.$GLOBALS['project'].'/tests/'.$path;

        if (file_exists($dir) === false) {
            \PerspectiveSimulator\Libs\Web::send404();
        }

        $ext = \PerspectiveSimulator\Libs\FileSystem::getExtension($path);
        if ($ext === 'php') {
            ob_start();
            include $dir;
            $contents = trim(ob_get_contents());
            ob_end_clean();
            echo $contents;
        } else {
            \PerspectiveSimulator\Libs\FileSystem::serveFile($dir);
        }
    break;

    case 'property':
       \PerspectiveSimulator\Requests\Property::serveFile($path);
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
