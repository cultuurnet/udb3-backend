<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\Token\AbstractToken;

class Impersonator
{
    /**
     * @var string|null
     */
    private $userId;

    /**
     * @var AbstractToken|null
     */
    private $jwt;

    /**
     * @var ApiKey|null
     */
    private $apiKey;

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getJwt(): ?AbstractToken
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
        $this->jwt = $metadata['auth_jwt'] ?? null;
        $this->apiKey = $metadata['auth_api_key'] ?? null;
    }
}
