<?php

namespace CultuurNet\UDB3\Offer\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface PermissionVoterInterface
{
    /**
     * @return bool
     */
    public function isAllowed(
        Permission $permission,
        StringLiteral $offerId,
        StringLiteral $userId
    );
}
