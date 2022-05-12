<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class ChangeOwner implements AuthorizableCommand
{
    private string $organizerId;

    private string $newOwnerId;

    public function __construct(string $organizerId, string $newOwnerId)
    {
        $this->organizerId = $organizerId;
        $this->newOwnerId = $newOwnerId;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getNewOwnerId(): string
    {
        return $this->newOwnerId;
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBewerken();
    }
}
