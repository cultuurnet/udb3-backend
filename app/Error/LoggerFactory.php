<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\JsonWebToken;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;
use Silex\Application;

final class LoggerFactory
{
    /**
     * @var Logger[]
     */
    private static $loggers;

    /**
     * @var StreamHandler[]
     */
    private static $streamHandlers = [];

    /**
     * @var SentryHandlerScopeDecorator|null
     */
    private static $sentryHandler;

    public static function create(
        Application $app,
        LoggerName $name,
        array $extraHandlers = []
    ): Logger {
        $loggerName = $name->getLoggerName();
        $fileNameWithoutSuffix = $name->getFileNameWithoutSuffix();

        if (!isset(self::$loggers[$loggerName])) {
            self::$loggers[$loggerName] = new Logger($loggerName);
            self::$loggers[$loggerName]->pushProcessor(new PsrLogMessageProcessor());

            $streamHandler = self::getStreamHandler($fileNameWithoutSuffix);
            $sentryHandler = self::getSentryHandler($app);

            $handlers = new GroupHandler(array_merge([$streamHandler, $sentryHandler], $extraHandlers));
            self::$loggers[$loggerName]->pushHandler($handlers);
        }

        return self::$loggers[$loggerName];
    }

    private static function getStreamHandler(string $name): StreamHandler
    {
        if (!isset(self::$streamHandlers[$name])) {
            self::$streamHandlers[$name] = new StreamHandler(__DIR__ . '/../../log/' . $name . '.log', Logger::DEBUG);
            self::$streamHandlers[$name]->pushProcessor(new ContextExceptionConverterProcessor());
        }

        return self::$streamHandlers[$name];
    }

    private static function getSentryHandler(Application $app): SentryHandlerScopeDecorator
    {
        if (!isset(self::$sentryHandler)) {
            self::$sentryHandler = new SentryHandlerScopeDecorator(
                new SentryHandler($app[HubInterface::class], Logger::ERROR),
                $app[JsonWebToken::class] ?? null,
                $app[ApiKey::class] ?? null,
                $app['api_name'] ?? null
            );
        }

        return self::$sentryHandler;
    }
}
