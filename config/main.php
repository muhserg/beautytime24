<?php

if(@file_exists(__DIR__ . '/db.local.php') === false){
    echo "Not DB connection settings.";
    exit;
}

$db_params = require(__DIR__ . '/db.local.php');

if (php_sapi_name() !== "cli") {
    $_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
}

if(empty($db_params)){
    echo "Not DB connection settings.";
    exit;
}

require(dirname(__FILE__) . "/consts.php");

return [
    'basePath' => dirname(__FILE__) . '/../protected',
    'runtimePath' => YII_PATH_RUNTIME,
    'defaultController' => 'main',
    'charset' => 'utf-8',
    'name' => 'bt',
    'language' => 'ru',
    'preload' => ['log', 'session', 'mail'],
    'import' => [
        'application.models.*',
        'application.dsp.*',
        'application.enum.*',
        'application.controllers.*',
        'application.components.*',
        'application.services.*',
        'application.helpers.*',
        'application.formatters.*',
        'application.lib.*',
    ],
    'components' => [
        //краткие ссылки - если хотим через index.php?r= то нужно закомментировать данный компонент
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                //'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>/?id=<id>',
            ],
        ],
        'cache' => [
            'class' => 'CFileCache',
        ],
        'db' => $db_params,
        'errorHandler' => [],
        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ],
                /*[
                    'class' => 'CWebLogRoute',
                    'categories' => 'application',
                    'levels' => 'error, warning, trace, profile, info',
                ],*/
            ],
        ],
    ],
    'params' => [
        'adminEmail' => 'info@' . SITE_NAME . '.ru',
    ],
];
