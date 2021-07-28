<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface PermissionVoter
{
    /**
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    );
}
