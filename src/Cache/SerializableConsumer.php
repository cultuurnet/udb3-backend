<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;

final class SerializableConsumer implements Consumer
{
    private ApiKey $apiKey;

    private ?string $defaultQuery;

    private array $permissionGroupIds;

    private ?string $name;

    private bool $isBlocked;

    private bool $isRemoved;

    private function __construct(
        ApiKey $apiKey,
        ?string $defaultQuery,
        array $permissionGroupIds,
        ?string $name,
        bool $isBlocked,
        bool $isRemoved
    ) {
        $this->apiKey = $apiKey;
        $this->defaultQuery = $defaultQuery;
        $this->permissionGroupIds = $permissionGroupIds;
        $this->name = $name;
        $this->isBlocked = $isBlocked;
        $this->isRemoved = $isRemoved;
    }

    public static function serialize(Consumer $consumer): array
    {
        return [
            'api_key' => $consumer->getApiKey(),
            'default_query' => $consumer->getDefaultQuery(),
            'permission_group_ids' => $consumer->getPermissionGroupIds(),
            'name' => $consumer->getName(),
            'is_blocked' => $consumer->isBlocked(),
            'is_removed' => $consumer->isRemoved(),
        ];
    }

    public static function deserialize(array $consumerAsArray): Consumer
    {
        return new SerializableConsumer(
            $consumerAsArray['api_key'],
            $consumerAsArray['default_query'],
            $consumerAsArray['permission_group_ids'],
            $consumerAsArray['name'],
            $consumerAsArray['is_blocked'],
            $consumerAsArray['is_removed']
        );
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function getDefaultQuery(): ?string
    {
        return $this->defaultQuery;
    }

    public function getPermissionGroupIds(): array
    {
        return $this->permissionGroupIds;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function isRemoved(): bool
    {
        return $this->isRemoved;
    }
}
