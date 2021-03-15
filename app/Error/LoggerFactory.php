<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;
use Silex\Application;

final class LoggerFactory
{
    public static function create(
        Application $app,
        string $fileNameWithoutSuffix,
        ?string $loggerName = null
    ): Logger {
        $logger = new Logger($loggerName ?? 'logger.' . $fileNameWithoutSuffix);

        $fileLogger = new StreamHandler(__DIR__ . '/../../log/' . $fileNameWithoutSuffix . '.log', Logger::DEBUG);
        $fileLogger->pushProcessor(new ContextExceptionConverterProcessor());
        $fileLogger->pushProcessor(new ContextFilteringProcessor(['tags']));
        $logger->pushHandler($fileLogger);

        $logger->pushHandler(new SentryHandler($app[HubInterface::class], Logger::ERROR));

        return $logger;
    }
}
