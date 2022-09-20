<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface UserPermissionsReadRepositoryInterface
{
    /**
     * @return Permission[]
     */
    public function getPermissions(string $userId): array;

    public function hasPermission(string $userId, Permission $permission): bool;
}
