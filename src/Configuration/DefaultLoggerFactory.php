<?php
/**
 * This file is a part of mercure-router-php package.
 *
 * (c) Swag Industries <nek.dev@gmail.com>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

declare(strict_types=1);

namespace SwagIndustries\MercureRouter\Configuration;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use Amp\ByteStream;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LogLevel;

final class DefaultLoggerFactory
{
    public static function createDefaultLogger($debug = false): Logger
    {
        $logHandler = new StreamHandler(ByteStream\getStdout(), level: $debug ? LogLevel::DEBUG : LogLevel::WARNING);
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter());
        $logger = new Logger('server');
        $logger->pushHandler($logHandler);
        $logger->useLoggingLoopDetection(false);

        return $logger;
    }
}
