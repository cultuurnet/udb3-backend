<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UWP;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use GuzzleHttp\Client;

final class UwpServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            UwpApiConnector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            UwpApiConnector::class,
            function () use ($container) {
                return new UwpApiConnector(
                    new Client(),
                    $container->get('config')['uwp']['client_id'],
                    $container->get('config')['uwp']['client_secret'],
                    LoggerFactory::create($container, LoggerName::forWeb())
                );
            }
        );
    }
}
