<?php

/**
 *  Отправка смс при создании заказа
 *
 *  php protected/cron/SendOrderSms.php
 */

require_once(__DIR__ . '/../../config/consts.php');
require_once __DIR__ . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . '/../../base/yiilite.php');
$config = dirname(__FILE__) . '/../../config/main.php';

Yii::createApplication('CWebApplication', $config);

SenderService::getInstance()->sendSmsAfterOrderCreate();
