<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedConsumerReadRepository implements ConsumerReadRepository
{
    private ConsumerReadRepository $baseConsumerReadRepository;

    private CacheInterface $cache;

    public function __construct(ConsumerReadRepository $baseConsumerReadRepository, CacheInterface $cache)
    {
        $this->baseConsumerReadRepository = $baseConsumerReadRepository;
        $this->cache = $cache;
    }

    public function getConsumer(ApiKey $apiKey): ?Consumer
    {
        $serializableConsumer = $this->cache->get(
            $apiKey->toString(),
            function () use ($apiKey) {
                $consumer = $this->baseConsumerReadRepository->getConsumer($apiKey);
                return $consumer !== null ? SerializableConsumer::serialize($consumer) : null;
            }
        );
        if ($serializableConsumer !== null) {
            return SerializableConsumer::deserialize($serializableConsumer);
        }
        return null;
    }
}
