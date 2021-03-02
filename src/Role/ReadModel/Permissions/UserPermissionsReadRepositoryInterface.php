<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface UserPermissionsReadRepositoryInterface
{
    /**
     * @return Permission[]
     */
    public function getPermissions(StringLiteral $userId);
}
