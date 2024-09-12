<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;

final class Impersonator
{
    private ?string $userId = null;

    private ?JsonWebToken $jwt = null;

    private ?ApiKey $apiKey = null;

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getJwt(): ?JsonWebToken
    {
        return $this->jwt;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function impersonate(Metadata $metadata): void
    {
        $metadata = $metadata->serialize();

        $this->userId = $metadata['user_id'];
        $this->jwt = !empty($metadata['auth_jwt']) ? new JsonWebToken($metadata['auth_jwt']) : null;
        $this->apiKey = $metadata['auth_api_key'] ?? null;
    }
}
