<?php

namespace CultuurNet\UDB3\Role\ReadModel\Constraints;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

interface UserConstraintsReadRepositoryInterface
{
    /**
     * @return StringLiteral[]
     */
    public function getByUserAndPermission(
        StringLiteral $userId,
        Permission $permission
    );
}
