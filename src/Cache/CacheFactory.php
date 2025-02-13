<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use Predis\Client;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheFactory
{
    public static function create(
        ContainerInterface $container,
        string $namespace,
        int $defaultLifeTime
    ): CacheInterface {
        return new RedisAdapter($container->get(Client::class), $namespace, $defaultLifeTime);
    }
}
