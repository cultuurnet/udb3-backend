<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Uitwisselingsplatform;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use GuzzleHttp\Client;

final class UitwisselingsplatformServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            UitwisselingsplatformApiConnector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            UitwisselingsplatformApiConnector::class,
            function () use ($container) {
                return new UitwisselingsplatformApiConnector(
                    new Client(),
                    $container->get('config')['uitwisselingsplatform']['client_id'],
                    $container->get('config')['uitwisselingsplatform']['client_secret'],
                    LoggerFactory::create($container, LoggerName::forWeb())
                );
            }
        );
    }
}
