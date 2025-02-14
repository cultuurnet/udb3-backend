<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use Predis\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheFactory
{
    public static function create(
        Client $client,
        string $namespace,
        int $defaultLifeTime
    ): CacheInterface {
        return new RedisAdapter($client, $namespace, $defaultLifeTime);
    }
}
