<?php

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultureFeed_User;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Jwt\Udb3Token;

class Impersonator
{
    /**
     * @var CultureFeed_User
     */
    private $user;

    /**
     * @var Udb3Token|null
     */
    private $jwt;

    /**
     * @var ApiKey|null
     */
    private $apiKey;

    public function getUser(): ?CultureFeed_User
    {
        return $this->user;
    }

    public function getJwt(): ?Udb3Token
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

        $this->user = new CultureFeed_User();
        $this->user->id = $metadata['user_id'];
        $this->user->nick = $metadata['user_nick'];
        $this->user->mbox = $metadata['user_email'] ?? null;
        $this->jwt = $metadata['auth_jwt'] ?? null;
        $this->apiKey = $metadata['auth_api_key'] ?? null;
    }
}
