<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use Monolog\Handler\GroupHandler;
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
        ?string $loggerName = null,
        array $extraHandlers = []
    ): Logger {
        $logger = new Logger($loggerName ?? 'logger.' . $fileNameWithoutSuffix);

        $fileLogger = new StreamHandler(__DIR__ . '/../../log/' . $fileNameWithoutSuffix . '.log', Logger::DEBUG);
        $fileLogger->pushProcessor(new ContextExceptionConverterProcessor());

        $sentryHandler = new SentryHandler($app[HubInterface::class], Logger::ERROR);
        $sentryHandler->pushProcessor(
            new SentryTagsProcessor(
                $app['jwt'] ?? null,
                $app['auth.api_key'] ?? null,
                $app['api_name'] ?? null
            )
        );
        $logger->pushHandler($sentryHandler);

        $handlers = new GroupHandler(array_merge([$fileLogger, $sentryHandler], $extraHandlers));
        $logger->pushHandler($handlers);

        return $logger;
    }
}
