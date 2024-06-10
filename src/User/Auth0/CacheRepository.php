<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Auth0;

use DateTimeImmutable;
use Doctrine\Common\Cache\Cache;

class CacheRepository implements Auth0ManagementTokenRepository
{
    private const TOKEN_KEY = 'auth0_token';

    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function token(): ?Auth0Token
    {
        if (!$this->cache->contains(self::TOKEN_KEY)) {
            return null;
        }

        $tokenAsArray = json_decode($this->cache->fetch(self::TOKEN_KEY), true);

        return new Auth0Token(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuedAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function store(Auth0Token $token): void
    {
        $tokenAsJson = json_encode(
            [
                'token' => $token->getToken(),
                'issuedAt' => $token->getIssuedAt()->format(DATE_ATOM),
                'expiresIn' => $token->getExpiresIn(),
            ],
            JSON_THROW_ON_ERROR
        );

        $this->cache->save(self::TOKEN_KEY, $tokenAsJson);
    }
}
