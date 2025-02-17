<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use Doctrine\Common\Cache\PredisCache;
use League\Container\Argument\Literal\CallableArgument;
use Predis\Client;

final class CacheServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'cache',
            Client::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'cache',
            new CallableArgument(
                fn ($cacheType) => new PredisCache(
                    new Client(
                        $container->get('config')['cache']['redis'],
                        ['prefix' => $cacheType . '_'],
                    )
                )
            )
        );

        $container->addShared(
            Client::class,
            new Client(
                $container->get('config')['cache']['redis']
            )
        );
    }
}
