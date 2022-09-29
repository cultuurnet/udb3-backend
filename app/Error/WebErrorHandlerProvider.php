<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use League\Container\ServiceProvider\AbstractServiceProvider;

final class WebErrorHandlerProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            ErrorLogger::class,
            WebErrorHandler::class,
        ];
        return in_array($id, $services, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ErrorLogger::class,
            function (): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($app, LoggerName::forWeb())
                );
            }
        );

        $container->addShared(
            WebErrorHandler::class,
            function (): WebErrorHandler {
                return new WebErrorHandler(
                    $app[ErrorLogger::class],
                    $app['debug'] === true
                );
            }
        );
    }
}
