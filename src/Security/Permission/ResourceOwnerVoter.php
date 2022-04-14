<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

class ResourceOwnerVoter implements PermissionVoter
{
    private ResourceOwnerQuery $permissionRepository;

    private array $cache = [];

    public function __construct(ResourceOwnerQuery $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $cacheKey = $itemId->toNative() . $userId->toNative();
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $editableEvents = $this->permissionRepository->getEditableResourceIds($userId);
        $this->cache[$cacheKey] = in_array($itemId, $editableEvents);

        return $this->cache[$cacheKey];
    }
}
