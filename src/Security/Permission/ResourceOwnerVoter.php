<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class ResourceOwnerVoter implements PermissionVoter
{
    private ResourceOwnerQuery $permissionRepository;

    private bool $enableCache;

    private array $cache = [];

    public function __construct(ResourceOwnerQuery $permissionRepository, bool $enableCache)
    {
        $this->permissionRepository = $permissionRepository;
        $this->enableCache = $enableCache;
    }

    public function isAllowed(
        Permission $permission,
        string $itemId,
        string $userId
    ): bool {
        if (!$this->enableCache) {
            return in_array($itemId, $this->permissionRepository->getEditableResourceIds($userId));
        }

        $cacheKey = $itemId . $userId;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $this->cache[$cacheKey] = in_array($itemId, $this->permissionRepository->getEditableResourceIds($userId));

        return $this->cache[$cacheKey];
    }
}
