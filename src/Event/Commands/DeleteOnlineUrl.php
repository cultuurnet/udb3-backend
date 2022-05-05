<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class DeleteOnlineUrl implements AuthorizableCommand
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
