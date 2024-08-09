<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\ManagementToken;

use CultuurNet\UDB3\Json;
use DateTimeImmutable;
use Doctrine\Common\Cache\Cache;

class CacheRepository implements TokenRepository
{
    private const TOKEN_KEY = 'management_token';

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

        $tokenAsArray = Json::decodeAssociatively($this->cache->fetch(self::TOKEN_KEY));

        return new ManagementToken(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuedAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function store(ManagementToken $token): void
    {
        $tokenAsJson = Json::encode(
            [
                'token' => $token->getToken(),
                'issuedAt' => $token->getIssuedAt()->format(DATE_ATOM),
                'expiresIn' => $token->getExpiresIn(),
            ]
        );

        $this->cache->save(self::TOKEN_KEY, $tokenAsJson);
    }
}
