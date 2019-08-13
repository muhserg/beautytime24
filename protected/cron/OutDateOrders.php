<?php

/**
 * Перевод просроченных заказов в статус 'Просроченные'
 * php protected/cron/OutDateOrders.php
 */

require_once(__DIR__ . '/../../config/consts.php');
require_once __DIR__ . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . '/../../base/yiilite.php');
$config = dirname(__FILE__) . '/../../config/main.php';

Yii::createApplication('CWebApplication', $config);

OrderService::getInstance()->outDateOrders();
