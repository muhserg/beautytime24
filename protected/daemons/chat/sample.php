#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: sergey.komarov
 * Date: 25.01.2019
 *
 * Описание конфигурации
 *
 *
 * $config = array(
 * 'class' => 'class that will handle connections',
 * 'pid' => 'pid-file',
 * 'websocket' => 'socket that will handle websocket-connections',
 * //'eventDriver' => 'handler type'
 * //socket_select (default, not need install, can handle maximum 2000 connections) - http://php.net/manual/en/function.socket-select.php
 * //pecl/event (need install) - https://pecl.php.net/package/event
 * //pecl/libevent (need install) - https://pecl.php.net/package/libevent
 * //'localsocket' => 'socket for handle local-connections (without websocket-protocol)'
 * //'master' => 'for connect to other local socket (without websocket-protocol)'
 * );
 */

if (empty($argv[1]) || !in_array($argv[1], ['start', 'stop', 'restart'])) {
    $argv[1] = 'start';
}

require_once(__DIR__ . '/../../../config/consts.php');
$config = [
    'class' => 'morozovsk\websocket\ChatWebsocketDaemonHandler',
    'pid' => __DIR__ . '/../../runtime/websocket_chat.pid',
    'websocket' => 'tcp://'.WEB_SOCKET_SERVER_HOST,
];


//posix_getpid() не работает в Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    function posix_getpid()
    {
    }
    function posix_getpgid()
    {
    }
}

require_once __DIR__ . '/../../../vendor/autoload.php';
class_alias('morozovsk\websocket\GenericSelect', 'morozovsk\websocket\Generic');
require_once __DIR__ . '/../../lib/BtChatLogger.php';
require_once __DIR__ . '/BtChatServerWin.php';
require_once __DIR__ . '/ChatWebsocketDaemonHandler.php';

$WebsocketServer = new morozovsk\websocket\BtChatServerWin($config);

call_user_func([$WebsocketServer, $argv[1]]);
