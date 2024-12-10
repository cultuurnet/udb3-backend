<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractPermissionCommand extends AbstractCommand
{
    private string $rolePermission;

    public function __construct(
        Uuid $uuid,
        Permission $rolePermission
    ) {
        parent::__construct($uuid);

        // The built-in serialize call does not work on Enum.
        // Just store them internally as string but expose as Enum.
        $this->rolePermission = $rolePermission->toString();
    }

    public function getRolePermission(): Permission
    {
        return new Permission($this->rolePermission);
    }
}
