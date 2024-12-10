<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

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
        return $this->getUuid()->toString();
    }

    public function getPermission(): Permission
    {
        return Permission::gebruikersBeheren();
    }
}
