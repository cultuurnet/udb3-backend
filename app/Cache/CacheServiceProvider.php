<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use Doctrine\Common\Cache\PredisCache;
use League\Container\Argument\Literal\CallableArgument;
use Predis\Client;
use RectorPrefix202209\Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final class CacheServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'cache',
            CacheInterface::class,
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
            CacheInterface::class,
            new RedisAdapter(
                new Client(
                    $container->get('config')['cache']['redis']
                ),
                'users' . '_',
                86400,
            ),
        );
    }
}
