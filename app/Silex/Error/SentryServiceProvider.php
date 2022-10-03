<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use function Sentry\init;

final class SentryServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [HubInterface::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            HubInterface::class,
            function () use ($container) {
                init([
                    'dsn' => $container->get('config')['sentry']['dsn'],
                    'environment' => $container->get('config')['sentry']['environment'],
                ]);

                return SentrySdk::getCurrentHub();
            }
        );
    }
}
