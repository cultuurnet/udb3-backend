<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ManagementTokenProvider
{
    private const CACHE_KEY = 'token';
    private const CACHE_TTL = 300;

    private ManagementTokenGenerator $tokenGenerator;

    private CacheInterface $cache;

    public function __construct(
        ManagementTokenGenerator $tokenGenerator,
        CacheInterface $cache
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->cache = $cache;
    }

    public function token(): string
    {
        return (string) $this->cache->get(
            self::CACHE_KEY,
            function (ItemInterface $item): string {
                $token = $this->tokenGenerator->newToken();

                // Expire from the cache ~5 minutes before the token itself expires, so we never hand
                // out a token that is about to expire.
                $item->expiresAfter(max($token->getExpiresIn() - self::CACHE_TTL, 0));

                return $token->getToken();
            }
        );
    }
}
