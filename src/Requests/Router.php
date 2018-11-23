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
$project   = array_shift($pathParts);
$path      = implode('/', $pathParts);

\PerspectiveSimulator\Bootstrap::load($project);

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
        \PerspectiveSimulator\Libs\Web::send404();
    break;
}
