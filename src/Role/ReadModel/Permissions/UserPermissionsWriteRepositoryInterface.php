<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface UserPermissionsWriteRepositoryInterface
{
    public function removeRole(UUID $roleId): void;


    public function addRolePermission(UUID $roleId, Permission $permission): void;


    public function removeRolePermission(UUID $roleId, Permission $permission): void;


    public function addUserRole(string $userId, UUID $roleId): void;


    public function removeUserRole(string $userId, UUID $roleId): void;
}
