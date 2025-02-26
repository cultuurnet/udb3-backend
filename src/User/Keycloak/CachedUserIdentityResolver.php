<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Keycloak;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use InvalidArgumentException;
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
        return $this->getUserFromCache(
            $userId,
            'user_id',
            fn () => $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserById($userId))
        );
    }

    public function getUserByEmail(EmailAddress $email): ?UserIdentityDetails
    {
        return $this->getUserFromCache(
            $email->toString(),
            'email',
            fn () => $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserByEmail($email))
        );
    }

    public function getUserByNick(string $nick): ?UserIdentityDetails
    {
        return $this->getUserFromCache(
            $nick,
            'nick',
            fn () => $this->getUserIdentityDetailsAsArray($this->userIdentityResolver->getUserByNick($nick))
        );
    }

    private function createCacheKey(string $value, string $property): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $value . '_' . $property);
    }

    private function getUserFromCache(string $key, string $type, callable $callback): ?UserIdentityDetails
    {
        try {
            return $this->deserializeUserIdentityDetails(
                $this->cache->get(
                    $this->createCacheKey($key, $type),
                    fn () => $callback()
                )
            );
        } catch (InvalidArgumentException $exception) {
            return null;
        }
    }

    private function getUserIdentityDetailsAsArray(?UserIdentityDetails $userIdentityDetails): array
    {
        if ($userIdentityDetails === null) {
            throw new InvalidArgumentException();
        }
        return $userIdentityDetails->jsonSerialize();
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
