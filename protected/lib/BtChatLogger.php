<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Логирование чата сайта (сделано через singleton)
 */
class BtChatLogger
{
    /**
     * @var Logger[]
     */
    private static $logger = [];

    /**
     * @param string $channel
     * @param string $level
     * @return Logger
     */
    public static function getLogger($channel = 'main', $level = 'info')
    {
        if (!array_key_exists($channel, self::$logger)) {
            try {
                self::$logger[$channel] = new Logger($channel);
                self::$logger[$channel]->pushProcessor(new Monolog\Processor\UidProcessor());
                self::$logger[$channel]->pushProcessor(new Monolog\Processor\WebProcessor());

                $handler = new StreamHandler(__DIR__ . '/../../log/chat.log', $level);
                $handler->setFormatter(new \Monolog\Formatter\JsonFormatter());
                self::$logger[$channel]->pushHandler($handler);
            }
            catch(Exception $e){
                //не обрабатываем
            }
        }

        return self::$logger[$channel];
    }
}
