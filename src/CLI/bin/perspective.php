<?php
/**
 * Perspective command for the perspective simulator
 *
 * @package    Perspective
 * @subpackage Simulator
 * @author     Squiz Pty Ltd <products@squiz.net>
 * @copyright  2018 Squiz Pty Ltd (ABN 77 084 670 600)
 */

// Prepare script for cli run.
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

error_reporting(E_ALL | E_STRICT);
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Get options
$opts = getopt(
    'p:hiS::c',
    [
        'project:',
        'help',
        'install',
        'server::',
        'clean',
        'init::',
        'repo-url::',
    ]
);

if (isset($opts['init']) === true) {
    if (empty($opts['init']) === true) {
        exit("System Name is required.\n");
    }

    $systemName = str_replace(' ', '_', $opts['init']);
    $systemName = str_replace('-', '_', $systemName);
    $parts     = explode('_', $systemName);
    if (count($parts) === 1) {
        if (strtoupper($parts[0]) === $parts[0]) {
            $parts[0] = ucfirst(strtolower($parts[0]));
        }
    } else {
        foreach ($parts as $idx => $part) {
            $parts[$idx] = ucfirst(strtolower($part));
        }
    }

    $systemName = implode('', $parts);
    $systemDir  = getcwd().'/'.$systemName;
    if (mkdir($systemDir) === false) {
        exit(sprintf('Unable to create system directory "%s"', $systemDir));
    }

    if (mkdir($systemDir.'/projects') === false) {
        exit(sprintf('Unable to create projects directory "%s"', $systemDir.'/projects'));
    }

    $gitignore = $systemDir.'/.gitignore';
    file_put_contents($gitignore, '/simulator/
/vendor/
composer.lock');

    $composer = $systemDir.'/composer.json';
    file_put_contents(
        $composer,
        json_encode(
            [
                'name'         => $systemName,
                'description'  => $systemName,
                'repositories' => [
                    [
                        'type'    => 'path',
                        'url'     => '../PerspectiveSimulator',
                        'options' => [
                            'symlink' => false,
                        ],
                    ],
                ],
                'require'      => [
                    'Perspective/Simulator' => '@dev',
                ],
            ],
            128
        )
    );

    $phpunit = $systemDir.'/phpunit.xml.dist';
    file_put_contents($phpunit, '<phpunit bootstrap="vendor/autoload.php" stderr="true">
    <testsuites>
        <testsuite name="'.$systemName.'">
            <directory>projects/*/tests</directory>
        </testsuite>
    </testsuites>
</phpunit>');

    $systemInfo = $systemDir.'/system_info.json';
    file_put_contents(
        $systemInfo,
        json_encode(
            [
                'name'      => $systemName,
                'tag'       => 'Development',
                'colour'    => 'red',
                'systemURL' => '',
                'showTag'   => true,
            ],
            128
        )
    );

    if (isset($opts['repo-url']) === true && empty($opts['repo-url']) === false) {
        // Repo url set so lets initialise it.
        exec('git -C '.$systemDir.' init');
        exec('git -C '.$systemDir. ' remote add origin '.$opts['repo-url']);

        // TODO:: should we make the initial commit?
        // exec('git -C '.$systemDir. ' add');
        // exec('git -C '.$systemDir. ' commit -m "Initial commit"');
        // exec('git -C '.$systemDir. ' push -u origin master');
    }

    chdir($systemDir);
    exec('composer install');
    $opts['i'] = true;
}

// Workout the current project and if the simulator is installed so we can run our actions.
$simPath      = '/vendor/Perspective/Simulator';
$cwd          = getcwd();
$proot        = $cwd;
$project      = ($opts['p'] ?? $opts['project'] ?? null);
$prevBasename = null;
while (file_exists($proot.$simPath) === false) {
    if ($project === null) {
        $prevBasename = basename($proot);
    }

    $proot = dirname($proot);
    if ($proot === '/') {
        exit('Unable to find perspective simulator.');
    }

    if (basename($proot) === 'projects' && $project === null) {
        $project = $prevBasename;
    }

}

include_once $proot.'/vendor/autoload.php';


if (isset($opts['S']) === true || isset($opts['server']) === true) {
    $host = ($opts['S'] ?? $opts['server'] ?? '0.0.0.0:8000');
    if (empty($host) === true) {
        $host = '0.0.0.0:8000';
    }

    \PerspectiveSimulator\CLI\Terminal::printLine(sprintf('Perspective Simulator listening on: http://%s', $host));
    \PerspectiveSimulator\CLI\Terminal::printLine('Press Ctrl-C to quit.');
    $router = $proot.'/vendor/Perspective/Simulator/src/Requests/Router.php';
    exec('php -S '.$host.' '.$router);
    exit(1);
}

$help = false;
if (isset($opts['h']) === true || isset($opts['help']) === true) {
    $help = true;
}

$install = false;
if (isset($opts['i']) === true || isset($opts['install']) === true) {
    $install = true;
}

$clean = false;
if (isset($opts['c']) === true || isset($opts['clean']) === true) {
    $clean = true;
}

$console = \PerspectiveSimulator\CLI\Console::getInstance();
$args    = [
    'project' => $project,
    'install' => $install,
    'help'    => $help,
    'clean'   => $clean,
    'argv'    => $argv,
];

if ($install === false) {
    $console->loadProject($project, $args);
}

$console->run($args);
