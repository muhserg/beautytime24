<?php

if(@file_exists(__DIR__ . '/db.local.php') === false){
    echo "Not DB connection settings.";
    exit;
}

$db_params = require(__DIR__ . '/db.local.php');

require(__DIR__ . "/consts.php");

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return [
    'basePath' => __DIR__ . '/../protected',
    'runtimePath' => YII_PATH_RUNTIME,
    'name' => 'bt Console',
    // preloading 'log' component
    'preload' => ['log'],
    'import' => [
        'application.components.*',
    ],
    // application components
    'components' => [
        'cache' => [
            'class' => 'CFileCache',
        ],
        'db' => $db_params,
        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ],
            ],
        ],
    ],
    'commandMap' => [
        'migrate' => [
            'class' => 'system.cli.commands.MigrateCommand',
            'connectionID' => 'db',
        ],
    ],
];
