<?php

require(__DIR__ . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/base/yiilite.php');
require_once(dirname(__FILE__) . '/config/consts.php');

$config = dirname(__FILE__) . '/config/main.php';

Yii::createApplication('CWebApplication', $config)->run();
