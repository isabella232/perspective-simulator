<?php
header('Content-Type: application/json');

$nodes = [
    [
        'id'       => '/',
        'parentid' => null,
        'title'    => 'Projects',
        'content'  => 'Projects',
        'classes'  => [],
        'icon'     => 'folder-close',
    ],
];

$path = dirname(__FILE__, 7).'/projects';
$projects = glob($path.'/*');
foreach ($projects as $projectPath) {
    $projectName = basename($projectPath);
    $nodes[] = [
        'id'       => '/'.$projectName,
        'parentid' => '/',
        'title'    => $projectName,
        'content'  => $projectName,
        'classes'  => [],
        'icon'     => 'briefcase',
    ];

    $projectPath .= '/src';

    // API
    $nodes[] = [
        'id'       => '/'.$projectName.'/API',
        'parentid' => '/'.$projectName,
        'title'    => 'API',
        'content'  => 'API',
        'classes'  => [],
        'icon'     => 'qrcode',
    ];

    if (is_file($projectPath.'/API/api.yaml') === true) {
        $nodes[] = [
            'id'       => '/'.$projectName.'/API/api.yaml',
            'parentid' => '/'.$projectName.'/API',
            'title'    => 'api.yaml',
            'content'  => 'api.yaml',
            'classes'  => [],
            'icon'     => 'book',
        ];

        $nodes[] = [
            'id'       => '/'.$projectName.'/API/Operations',
            'parentid' => '/'.$projectName.'/API',
            'title'    => 'Operations',
            'content'  => 'Operations',
            'classes'  => [],
            'icon'     => 'folder-close',
        ];

        $operations = glob($projectPath.'/API/Operations/*.php');
        foreach ($operations as $operationPath) {
            $operationName = basename($operationPath);
            $nodes[] = [
                'id'       => '/'.$projectName.'/API/Operations/'.$operationName,
                'parentid' => '/'.$projectName.'/API/Operations',
                'title'    => $operationName,
                'content'  => $operationName,
                'classes'  => [],
                'icon'     => 'cog',
            ];
        }
    }

    // App
    $nodes[] = [
        'id'       => '/'.$projectName.'/App',
        'parentid' => '/'.$projectName,
        'title'    => 'App',
        'content'  => 'App',
        'classes'  => [],
        'icon'     => 'folder-open',
    ];

    if (is_dir($projectPath.'/App') === true) {
        $di = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($projectPath.'/App', \RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($di as $file) {
            $appName = $file->getBasename();
            if ($appName[0] === '.') {
                continue;
            }

            $appPath = $file->getPathname();
            if ($file->isDir() === true) {
                $icon = 'folder-close';
            } else {
                $icon = 'file';
            }

            $parentPath = preg_replace('|.*/projects/'.$projectName.'/src/App/|', '', dirname($appPath).'/');

            $nodes[] = [
                'id'       => '/'.$projectName.'/App/'.ltrim($parentPath, '/').$appName,
                'parentid' => rtrim('/'.$projectName.'/App/'.$parentPath, '/'),
                'title'    => $appName,
                'content'  => $appName,
                'classes'  => [],
                'icon'     => $icon,
            ];
        }
    }


    // CDN
    $nodes[] = [
        'id'       => '/'.$projectName.'/CDN',
        'parentid' => '/'.$projectName,
        'title'    => 'CDN',
        'content'  => 'CDN',
        'classes'  => [],
        'icon'     => 'cloud',
    ];

    if (is_dir($projectPath.'/CDN') === true) {
        $di = new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($projectPath.'/CDN', \RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($di as $file) {
            $cdnName = $file->getBasename();
            if ($cdnName[0] === '.') {
                continue;
            }

            $cdnPath = $file->getPathname();
            if ($file->isDir() === true) {
                $icon = 'folder-close';
            } else {
                $icon = 'file';
            }

            $parentPath = preg_replace('|.*/projects/'.$projectName.'/src/CDN/|', '', dirname($cdnPath).'/');

            $nodes[] = [
                'id'       => '/'.$projectName.'/CDN/'.ltrim($parentPath, '/').$cdnName,
                'parentid' => rtrim('/'.$projectName.'/CDN/'.$parentPath, '/'),
                'title'    => $cdnName,
                'content'  => $cdnName,
                'classes'  => [],
                'icon'     => $icon,
            ];
        }
    }

    // Custom Types
    $nodes[] = [
        'id'       => '/'.$projectName.'/CustomTypes',
        'parentid' => '/'.$projectName,
        'title'    => 'Custom Types',
        'content'  => 'Custom Types',
        'classes'  => [],
        'icon'     => 'picture',
    ];

    if (is_dir($projectPath.'/CustomTypes/Data') === true) {
        $nodes[] = [
            'id'       => '/'.$projectName.'/CustomTypes/Data',
            'parentid' => '/'.$projectName.'/CustomTypes',
            'title'    => 'Data',
            'content'  => 'Data',
            'classes'  => [],
            'icon'     => 'folder-close',
        ];

        $types = glob($projectPath.'/CustomTypes/Data/*.json');
        foreach ($types as $typePath) {
            $typeName = substr(basename($typePath), 0, -5);
            $nodes[] = [
                'id'       => '/'.$projectName.'/CustomTypes/Data'.$typeName,
                'parentid' => '/'.$projectName.'/CustomTypes/Data',
                'title'    => $typeName,
                'content'  => $typeName,
                'classes'  => [],
                'icon'     => 'list-alt',
            ];
        }
    }

    // Properties
    $nodes[] = [
        'id'       => '/'.$projectName.'/Properties',
        'parentid' => '/'.$projectName,
        'title'    => 'Properties',
        'content'  => 'Properties',
        'classes'  => [],
        'icon'     => 'th-list',
    ];

    if (is_dir($projectPath.'/Properties/Data') === true) {
        $nodes[] = [
            'id'       => '/'.$projectName.'/Properties/Data',
            'parentid' => '/'.$projectName.'/Properties',
            'title'    => 'Data',
            'content'  => 'Data',
            'classes'  => [],
            'icon'     => 'folder-close',
        ];

        $props = glob($projectPath.'/Properties/Data/*.json');
        foreach ($props as $propPath) {
            $propName = substr(basename($propPath), 0, -5);
            $nodes[] = [
                'id'       => '/'.$projectName.'/Properties/Data'.$propName,
                'parentid' => '/'.$projectName.'/Properties/Data',
                'title'    => $propName,
                'content'  => $propName,
                'classes'  => [],
                'icon'     => 'wrench',
            ];
        }
    }

    if (is_dir($projectPath.'/Properties/User') === true) {
        $nodes[] = [
            'id'       => '/'.$projectName.'/Properties/User',
            'parentid' => '/'.$projectName.'/Properties',
            'title'    => 'User',
            'content'  => 'User',
            'classes'  => [],
            'icon'     => 'folder-close',
        ];

        $props = glob($projectPath.'/Properties/User/*.json');
        foreach ($props as $propPath) {
            $propName = substr(basename($propPath), 0, -5);
            $nodes[] = [
                'id'       => '/'.$projectName.'/Properties/User'.$propName,
                'parentid' => '/'.$projectName.'/Properties/User',
                'title'    => $propName,
                'content'  => $propName,
                'classes'  => [],
                'icon'     => 'wrench',
            ];
        }
    }

    // Storage
    $nodes[] = [
        'id'       => '/'.$projectName.'/Storage',
        'parentid' => '/'.$projectName,
        'title'    => 'Storage',
        'content'  => 'Storage',
        'classes'  => [],
        'icon'     => 'hdd',
    ];

    if (is_dir($projectPath.'/Stores/Data') === true) {
        $stores = glob($projectPath.'/Stores/Data/*');
        foreach ($stores as $storePath) {
            $storeName = basename($storePath);
            $nodes[] = [
                'id'       => '/'.$projectName.'/Storage'.$storeName,
                'parentid' => '/'.$projectName.'/Storage',
                'title'    => $storeName,
                'content'  => $storeName,
                'classes'  => [],
                'icon'     => 'tasks',
            ];
        }
    }

    if (is_dir($projectPath.'/Stores/User') === true) {
        $stores = glob($projectPath.'/Stores/User/*');
        foreach ($stores as $storePath) {
            $storeName = basename($storePath);
            $nodes[] = [
                'id'       => '/'.$projectName.'/Storage'.$storeName,
                'parentid' => '/'.$projectName.'/Storage',
                'title'    => $storeName,
                'content'  => $storeName,
                'classes'  => [],
                'icon'     => 'user',
            ];
        }
    }

}

#print_r($nodes);
echo json_encode($nodes);
