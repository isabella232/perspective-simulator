<?php
header('Content-Type: application/json');

$nodes = [
    [
        'id'       => '/',
        'parentid' => null,
        'title'    => 'Root',
        'content'  => 'Root',
        'classes'  => [],
        'icon'     => 'folder-close',
    ], [
        'id'       => '/projects',
        'parentid' => '/',
        'title'    => 'Projects',
        'content'  => 'Projects',
        'classes'  => [],
        'icon'     => 'folder-close',
    ], [
        'id'       => '/projects/Shared',
        'parentid' => '/projects',
        'title'    => 'Shared',
        'content'  => 'Shared',
        'classes'  => [],
        'icon'     => 'folder-close',
    ], [
        'id'       => '/projects/Commenting',
        'parentid' => '/projects',
        'title'    => 'Commenting',
        'content'  => 'Commenting',
        'classes'  => [],
        'icon'     => 'folder-close',
    ], [
        'id'       => '/projects/Feedback',
        'parentid' => '/projects',
        'title'    => 'Feedback',
        'content'  => 'Feedback',
        'classes'  => [],
        'icon'     => 'folder-close',
    ],
];

echo json_encode($nodes);
