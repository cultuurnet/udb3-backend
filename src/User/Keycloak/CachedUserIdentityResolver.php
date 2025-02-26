<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Symfony\Component\Cache\Exception\CacheException;
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
        $this->cache = $cache;
    }

    public function getUserById(string $userId): ?UserIdentityDetails
    {
        try {
            return $this->deserializeUserIdentityDetails(
                $this->cache->get(
                    $this->createCacheKey($userId, 'user_id'),
                    function () use ($userId) {
                        $userIdentityDetails = $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserById($userId));

                        if ($userIdentityDetails === null) {
                            throw new CacheException();
                        }

                        return $userIdentityDetails;
                    }
                )
            );
        } catch (CacheException $exception) {
            return null;
        }
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        try {
            return $this->deserializeUserIdentityDetails(
                $this->cache->get(
                    $this->createCacheKey($email->toString(), 'email'),
                    function () use ($email) {
                        $userIdentityDetails = $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserByEmail($email));

                        if ($userIdentityDetails === null) {
                            throw new CacheException();
                        }

                        return $userIdentityDetails;
                    }
                )
            );
        } catch (CacheException $exception) {
            return null;
        }
    }

    public function getUserByNick(string $nick): ?UserIdentityDetails
    {
        try {
            return $this->deserializeUserIdentityDetails(
                $this->cache->get(
                    $this->createCacheKey($nick, 'nick'),
                    function () use ($nick) {
                        $userIdentityDetails = $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserByNick($nick));

                        if ($userIdentityDetails === null) {
                            throw new CacheException();
                        }

                        return $userIdentityDetails;
                    }
                )
            );
        } catch (CacheException $exception) {
            return null;
        }
    }

    private function createCacheKey(string $value, string $property): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $value . '_' . $property);
    }

    private function getUserIdentityDetailsAsArray(?UserIdentityDetails $userIdentityDetails): ?array
    {
        if ($userIdentityDetails !== null) {
            return $userIdentityDetails->jsonSerialize();
        }
        return null;
    }

    private function deserializeUserIdentityDetails(?array $cachedUserIdentityDetails): ?UserIdentityDetails
    {
        return $cachedUserIdentityDetails !== null ? new UserIdentityDetails(
            $cachedUserIdentityDetails['uuid'],
            $cachedUserIdentityDetails['username'],
            $cachedUserIdentityDetails['email']
        ) : null;
    }
}
