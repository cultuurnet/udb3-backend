<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Temp;

final class InMemoryApiKeysMatchedToClientIds implements ApiKeysMatchedToClientIds
{
    public function __construct(readonly array $apiKeysMatchedToClientIds)
    {
    }

    public function getClientId(string $apiKey): string
    {
        if (!array_key_exists($apiKey, $this->apiKeysMatchedToClientIds)) {
            throw new UnmatchedApiKey($apiKey);
        }
        return $this->apiKeysMatchedToClientIds[$apiKey];
    }
}
