<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface UserPermissionsWriteRepositoryInterface
{
    public function removeRole(Uuid $roleId): void;


    public function addRolePermission(Uuid $roleId, Permission $permission): void;


    public function removeRolePermission(Uuid $roleId, Permission $permission): void;


    public function addUserRole(string $userId, Uuid $roleId): void;


    public function removeUserRole(string $userId, Uuid $roleId): void;
}
