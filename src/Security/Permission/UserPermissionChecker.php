<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

final class UserPermissionChecker
{
    private array $permissions;
    private PermissionVoter $permissionVoter;

    public function __construct(array $permissions, PermissionVoter $permissionVoter)
    {
        $this->permissions = $permissions;
        $this->permissionVoter = $permissionVoter;
    }

    public function getOwnedPermissions(string $resourceId, string $userId): array
    {
        $ownedPermissions = [];

        foreach ($this->permissions as $permission) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $permission,
                $resourceId,
                $userId
            );

            if ($hasPermission) {
                $ownedPermissions[] = $permission->toString();
            }
        }

        return $ownedPermissions;
    }

    public function hasPermission(string $offerId, string $userId): bool
    {
        return $this->permissionVoter->isAllowed(
            $this->permissions[0],
            $offerId,
            $userId
        );
    }
}
