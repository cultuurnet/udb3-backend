<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface UserPermissionsWriteRepositoryInterface
{
    public function removeRole(UUID $roleId);


    public function addRolePermission(UUID $roleId, Permission $permission);


    public function removeRolePermission(UUID $roleId, Permission $permission);


    public function addUserRole(StringLiteral $userId, UUID $roleId);


    public function removeUserRole(StringLiteral $userId, UUID $roleId);
}
