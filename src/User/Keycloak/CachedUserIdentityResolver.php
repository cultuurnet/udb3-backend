<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedUserIdentityResolver implements UserIdentityResolver
{
    private UserIdentityResolver $userIdentityResolver;

    private CacheInterface $cache;

    public function __construct(
        UserIdentityResolver $userIdentityResolver,
        CacheInterface $cache
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
        $this->cache =$cache;
    }
    public function getUserById(string $userId): ?UserIdentityDetails
    {
        return $this->cache->get(
            $this->createCacheKey($userId, 'user_id'),
            function () use ($userId) {
                return $this->userIdentityResolver->getUserById($userId);
            }
        );
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        return $this->cache->get(
            $this->createCacheKey($email->toString(), 'email'),
            function () use ($email) {
                return $this->userIdentityResolver->getUserByEmail($email);
            }
        );
    }

    public function getUserByNick(string $nick): ?UserIdentityDetails
    {
        return $this->cache->get(
            $this->createCacheKey($nick, 'nick'),
            function () use ($nick) {
                return $this->userIdentityResolver->getUserByNick($nick);
            }
        );
    }

    private function createCacheKey(string $value, string $property): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', 'user_identity_' . $value . '_' . $property);
    }
}
