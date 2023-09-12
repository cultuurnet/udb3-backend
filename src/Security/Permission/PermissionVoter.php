<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface PermissionVoter
{
    public function isAllowed(
        Permission $permission,
        string $itemId,
        string $userId
    ): bool;
}
