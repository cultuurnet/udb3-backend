<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface UserConstraintsReadRepositoryInterface
{
    /**
     * @return string[]
     */
    public function getByUserAndPermission(
        string $userId,
        Permission $permission
    ): array;
}
