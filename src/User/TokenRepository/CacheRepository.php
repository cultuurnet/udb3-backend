<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\TokenRepository;

use CultuurNet\UDB3\User\ManagementToken;
use DateTimeImmutable;
use Doctrine\Common\Cache\Cache;

class CacheRepository implements TokenRepository
{
    private const TOKEN_KEY = 'auth0_token';

    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function token(): ?ManagementToken
    {
        if (!$this->cache->contains(self::TOKEN_KEY)) {
            return null;
        }

        $tokenAsArray = json_decode($this->cache->fetch(self::TOKEN_KEY), true);

        return new ManagementToken(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuedAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function store(ManagementToken $token): void
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
