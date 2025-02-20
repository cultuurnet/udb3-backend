<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultureFeed_Consumer;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedConsumerAdapter;
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
        $consumerAsArray = $this->cache->get(
            $apiKey->toString(),
            function () use ($apiKey) {
                $consumer = $this->baseConsumerReadRepository->getConsumer($apiKey);
                return $consumer !== null ? $this->consumerAsArray($consumer) : null;
            }
        );
        if ($consumerAsArray !== null) {
            return $this->arrayToConsumer($consumerAsArray);
        }
        return null;
    }

    private function consumerAsArray(Consumer $consumer): array
    {
        return [
            'api_key' => $consumer->getApiKey()->toString(),
            'default_query' => $consumer->getDefaultQuery(),
            'permission_group_ids' => $consumer->getPermissionGroupIds(),
            'name' => $consumer->getName(),
            'status' => $consumer->isBlocked() ? 'BLOCKED' :
                ($consumer->isRemoved() ? 'REMOVED' : 'ACTIVE'),
        ];
    }

    private function arrayToConsumer(array $consumerAsArray): Consumer
    {
        $consumer = new CultureFeed_Consumer();
        $consumer->apiKeySapi3 = $consumerAsArray['api_key'];
        $consumer->searchPrefixSapi3 = $consumerAsArray['default_query'];
        $consumer->group = $consumerAsArray['permission_group_ids'];
        $consumer->name = $consumerAsArray['name'];
        $consumer->status = $consumerAsArray['status'];
        return new CultureFeedConsumerAdapter($consumer);
    }
}
