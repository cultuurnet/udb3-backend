<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\StringLiteral;

final class UserPermissionChecker
{
    private array $permissions;
    private PermissionVoter $permissionVoter;

    public function __construct(array $permissions, PermissionVoter $permissionVoter)
    {
        $this->permissions = $permissions;
        $this->permissionVoter = $permissionVoter;
    }

    public function getOwnedPermissions(string $resourceId, ?string $userId = null): array
    {
        $ownedPermissions = [];

        foreach ($this->permissions as $permission) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $permission,
                new StringLiteral($resourceId),
                new StringLiteral($userId)
            );

            if ($hasPermission) {
                $ownedPermissions[] = $permission->toString();
            }
        }

        return $ownedPermissions;
    }

    public function hasPermission(string $offerId, ?string $userId = null): bool
    {
        if ($userId) {
            return $this->permissionVoter->isAllowed(
                $this->permissions[0],
                new StringLiteral($offerId),
                new StringLiteral($userId)
            );
        }

        return false;
    }
}
