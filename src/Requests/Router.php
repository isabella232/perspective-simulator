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
ini_set('session.save_path', dirname(__DIR__, 5).'/simulator/sessions');
ini_set('session.save_handler', 'files');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.name', 'PERS_SIM_SESSID');
ini_set('session.auto_start', 0);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', true);
ini_set('session.serialize_handler', 'php');
ini_set('session.gc_probability', 25);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.lazy_write', 1);
ini_set('session.referer_check', '');
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cache_expire', 180);
ini_set('session.use_trans_sid', 0);
ini_set('session.sid_length', 64);
ini_set('session.sid_bits_per_character', 5);

include dirname(__DIR__, 4).'/autoload.php';

session_start();

register_shutdown_function(
    function () {
        // Access log.
        $accessLog = dirname(__DIR__, 5).'/simulator/access.log';
        file_put_contents(
            $accessLog,
            '['.date('d/M/Y H:i:s O', $_SERVER['REQUEST_TIME']).'] "'.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.($_SERVER['SERVER_PROTOCOL'] ?? '').'" '.http_response_code().'" "'.($_SERVER['HTTP_USER_AGENT'] ?? '').'" "'.($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')."\"\n",
            FILE_APPEND
        );

        // Fix the session.
        $usersSession                = $_SESSION;
        $_SESSION                    = $GLOBALS['SIM_SESSION'];
        $_SESSION['SANDBOX_SESSION'] = $usersSession;
    }
);

// Remove our own session from $_SESSION will be readded on shutdown.
$usersSession = ($_SESSION['SANDBOX_SESSION'] ?? []);
unset($_SESSION['SANDBOX_SESSION']);
$GLOBALS['SIM_SESSION'] = $_SESSION;
$_SESSION               = $usersSession;

$settings = [];
if (file_exists(dirname(__DIR__, 5).'/simulator/router-settings.json') === true) {
    $settings = json_decode(file_get_contents(dirname(__DIR__, 5).'/simulator/router-settings.json'), true);
}

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

$installedProjects = \PerspectiveSimulator\Libs\Util::jsonDecode(
    file_get_contents(\PerspectiveSimulator\Libs\FileSystem::getSimulatorDir().'/projects.json')
);

if ($type !== 'admin') {
    $v1 = array_shift($pathParts);
    $v2 = array_shift($pathParts);

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

$delay = rand(0, 2000);
if (isset($settings['latency']) === true && $settings['latency'] === true) {
    sleep(($delay / 1000));
}

if (isset($settings['failure']) === true && $settings['failure'] === true && $delay > 1500) {
    // Simulate a failed request.
    return;
}

switch ($type) {
    case 'api':
        $queryParams = [];
        parse_str(($_SERVER['QUERY_STRING'] ?? ''), $queryParams);

        $method = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));

        try {
            ob_start();
            $class    = $GLOBALS['projectNamespace'].'APIRouter';
            $response = $class::process($path, $method, $queryParams);

            if ($response === null) {
                $response = '';
            }

            ob_end_clean();
        } catch (\TypeError $e) {
            // Request failed so don't worry about saving, so disable the writes.
            \PerspectiveSimulator\Bootstrap::disableWrite();
            ob_end_clean();
            header('HTTP/1.1 500 Internal Server Error');
            throw new \PerspectiveAPI\Exception\InvalidArgumentException($e->getMessage());
        } catch (\Throwable $e) {
            // Request failed so don't worry about saving, so disable the writes.
            \PerspectiveSimulator\Bootstrap::disableWrite();
            ob_end_clean();
            header('HTTP/1.1 500 Internal Server Error');
            throw $e;
        }

        echo $response;
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
        $dir = str_replace('/src', '', $installedProjects[strtolower($GLOBALS['project'])]).'/tests/'.$path;

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
}//end switch


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
