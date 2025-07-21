<?php

namespace Services;


use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class AsyncLogger
{
    private static ?Logger $logger = null;

    public static function get(): Logger
    {
        if (!self::$logger) {
            self::$logger = new Logger('automation');
            self::$logger->pushHandler(
                (new RotatingFileHandler('logs/automation.log', 7))->setFormatter(new LineFormatter(null, null, true, true))
            );
        }
        return self::$logger;
    }
}
