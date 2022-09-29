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
            function () use ($container): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($container, LoggerName::forWeb())
                );
            }
        );

        $container->addShared(
            WebErrorHandler::class,
            function () use ($container): WebErrorHandler {
                return new WebErrorHandler(
                    $container->get(ErrorLogger::class),
                    $container->get('debug') === true
                );
            }
        );
    }
}
