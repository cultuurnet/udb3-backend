<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;

abstract class AbstractCommand implements AuthorizableCommand
{
    private UUID $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getItemId(): string
    {
        return $this->uuid->toNative();
    }

    public function getPermission(): Permission
    {
        return Permission::labelsBeheren();
    }
}
