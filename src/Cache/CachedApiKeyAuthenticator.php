<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cache;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticationException;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticator;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedApiKeyAuthenticator implements ApiKeyAuthenticator
{
    private ApiKeyAuthenticator $baseApiKeyAuthenticator;

    private CacheInterface $cache;

    public function __construct(ApiKeyAuthenticator $baseApiKeyAuthenticator, CacheInterface $cache)
    {
        $this->baseApiKeyAuthenticator = $baseApiKeyAuthenticator;
        $this->cache = $cache;
    }
    public function authenticate(ApiKey $apiKey): void
    {
        $isAuthenticated = $this->cache->get(
            $apiKey->toString(),
            function () use ($apiKey) {
                try {
                    $this->baseApiKeyAuthenticator->authenticate($apiKey);
                    return true;
                } catch (ApiKeyAuthenticationException $apiKeyAuthenticationException) {
                    return false;
                }
            }
        );
        if (!$isAuthenticated) {
            throw ApiKeyAuthenticationException::forApiKey($apiKey);
        }
    }
}
