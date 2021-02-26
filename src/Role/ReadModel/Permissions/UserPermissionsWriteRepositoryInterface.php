<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface UserPermissionsWriteRepositoryInterface
{
    public function removeRole(UUID $roleId);


    public function addRolePermission(UUID $roleId, Permission $permission);


    public function removeRolePermission(UUID $roleId, Permission $permission);


    public function addUserRole(StringLiteral $userId, UUID $roleId);


    public function removeUserRole(StringLiteral $userId, UUID $roleId);
}
