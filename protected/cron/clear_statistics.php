<?php

/**
 *  php protected/cron/clear_statistics.php
 */

require_once(__DIR__ . '/../../config/consts.php');
require_once __DIR__ . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . '/../../base/yiilite.php');
$config = dirname(__FILE__) . '/../../config/main.php';

Yii::createApplication('CWebApplication', $config);

StatisticService::getInstance()->clearStatistic();

//вынужден добавить сюда из-за того, что тарифный план хостинга не позволяет создавать более 2х cron-задач
OrderService::getInstance()->outDateOrders();
