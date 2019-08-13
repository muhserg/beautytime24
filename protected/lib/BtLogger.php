<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Логирование (сделано через singleton)
 */
class BtLogger
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
    public static function getLogger($channel = 'main', $level = 'error')
    {
        if (!array_key_exists($channel, self::$logger)) {
            try {
                self::$logger[$channel] = new Logger($channel);
                self::$logger[$channel]->pushProcessor(new Monolog\Processor\UidProcessor());
                self::$logger[$channel]->pushProcessor(new Monolog\Processor\WebProcessor());

                $handler = new StreamHandler(__DIR__ . '/../../log/mono.log', $level);
                $jsonFormatter = new \Monolog\Formatter\JsonFormatter();
                $jsonFormatter->includeStacktraces(true);
                $handler->setFormatter($jsonFormatter);

                self::$logger[$channel]->pushHandler($handler);
            } catch (Exception $e) {
                //не обрабатываем
            }
        }

        return self::$logger[$channel];
    }
}
