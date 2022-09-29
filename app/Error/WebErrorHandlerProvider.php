<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

use CultuurNet\UDB3\Silex\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Error\WebErrorHandler;

final class WebErrorHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ErrorLogger::class,
            WebErrorHandler::class,
        ];
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
