<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class CliErrorHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [ErrorLogger::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ErrorLogger::class,
            function () use ($container): ErrorLogger {
                return new ErrorLogger(
                    LoggerFactory::create($container, LoggerName::forCli())
                );
            }
        );
    }
}
