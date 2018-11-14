<?php
/**
 * Router for Perspective Simulator.
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace PerspectiveSimulator;

include dirname(__DIR__, 3).'/autoload.php';

if (isset($_SERVER['QUERY_STRING']) === true) {
    $path = str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
     // Remove the trailing slash if there is one.
}

$path    = trim($path, '/');
$project = strtolower(substr($path, 0, strpos($path, '/')));
$path    = substr($path, (strpos($path, '/') + 1));
$type    = strtolower(substr($path, 0, strpos($path, '/')));
$path    = substr($path, (strpos($path, '/') + 1));

\PerspectiveSimulator\Bootstrap::load($project);

switch ($type) {
    case 'api':
        $queryParams = [];
        parse_str(($_SERVER['QUERY_STRING'] ?? ''), $queryParams);

        $method = strtolower(($_SERVER['REQUEST_METHOD'] ?? ''));

        $class = $project.'\APIRouter';
        $class::process($path, $method, $queryParams);
    break;

    default:
        echo 'send 404';
    break;
}
