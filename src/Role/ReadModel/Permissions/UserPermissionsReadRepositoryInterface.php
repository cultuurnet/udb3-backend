<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface UserPermissionsReadRepositoryInterface
{
    /**
     * @param StringLiteral $userId
     * @return Permission[]
     */
    public function getPermissions(StringLiteral $userId);
}
