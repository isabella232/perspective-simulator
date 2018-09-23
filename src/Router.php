<?php
namespace PerspectiveSimulator;

include dirname(dirname(dirname(__DIR__))).'/autoload.php';

$path    = trim($_SERVER['REQUEST_URI'], '/');
$project = strtolower(substr($path, 0, strpos($path, '/')));
$path    = substr($path, (strpos($path, '/') +1));
$type    = strtolower(substr($path, 0, strpos($path, '/')));
$path    = substr($path, (strpos($path, '/') +1));

\PerspectiveSimulator\Bootstrap::load($project);

switch ($type) {
    case 'api':
        $queryParams = [];
        parse_str(($_SERVER['QUERY_STRING'] ?? ''), $queryParams);

        $method = strtolower($_SERVER['REQUEST_METHOD'] ?? '');

        $class = $project.'\APIRouter';
        $class::process($path, $method, $queryParams);
    break;

    default:
        echo 'send 404';
    break;
}
