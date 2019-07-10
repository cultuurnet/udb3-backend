<?php

namespace CultuurNet\UDB3\Silex;

use Broadway\Domain\Metadata;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use Lcobucci\JWT\Token as Jwt;

class Impersonator
{
    /**
     * @var \CultureFeed_User
     */
    private $user;

    /**
     * @var TokenCredentials|null
     */
    private $tokenCredentials;

    /**
     * @var Jwt|null
     */
    private $jwt;

    /**
     * @var ApiKey
     */
    private $apiKey;

    public function __construct()
    {
        $this->user = null;
        $this->tokenCredentials = null;
    }

    /**
     * @return \CultureFeed_User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return TokenCredentials|null
     */
    public function getTokenCredentials()
    {
        return $this->tokenCredentials;
    }

    /**
     * @return Jwt|null
     */
    public function getJwt()
    {
        return $this->jwt;
    }

    /**
     * @return ApiKey|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param Metadata $metadata
     */
    public function impersonate(Metadata $metadata)
    {
        $metadata = $metadata->serialize();

        $this->user = new \CultureFeed_User();
        $this->user->id = $metadata['user_id'];
        $this->user->nick = $metadata['user_nick'];

        // There might still be queued commands without this metadata because
        // it was added later.
        $this->user->mbox = isset($metadata['user_email']) ? $metadata['user_email'] : null;
        $this->jwt = isset($metadata['auth_jwt']) ? $metadata['auth_jwt'] : null;

        // It is also possible to work without ApiKey enabled. So this can be null.
        $this->apiKey = isset($metadata['auth_api_key']) ? $metadata['auth_api_key'] : null;

        $this->tokenCredentials = $metadata['uitid_token_credentials'];
    }
}
