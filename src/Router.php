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

include dirname(__DIR__, 3).'/autoload.php';

$path    = trim($_SERVER['REQUEST_URI'], '/');
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
