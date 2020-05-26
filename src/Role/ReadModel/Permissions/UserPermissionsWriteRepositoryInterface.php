<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface UserPermissionsWriteRepositoryInterface
{
    /**
     * @param UUID $roleId
     */
    public function removeRole(UUID $roleId);

    /**
     * @param Permission $permission
     * @param UUID $roleId
     */
    public function addRolePermission(UUID $roleId, Permission $permission);

    /**
     * @param Permission $permission
     * @param UUID $roleId
     */
    public function removeRolePermission(UUID $roleId, Permission $permission);

    /**
     * @param StringLiteral $userId
     * @param UUID $roleId
     */
    public function addUserRole(StringLiteral $userId, UUID $roleId);

    /**
     * @param StringLiteral $userId
     * @param UUID $roleId
     */
    public function removeUserRole(StringLiteral $userId, UUID $roleId);
}
