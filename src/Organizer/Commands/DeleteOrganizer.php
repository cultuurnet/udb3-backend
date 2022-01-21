<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class DeleteOrganizer implements AuthorizableCommand
{
    private string $organizerId;

    public function __construct(string $organizerId)
    {
        $this->organizerId = $organizerId;
    }

    public function getItemId(): string
    {
        return $this->organizerId;
    }

    public function getPermission(): Permission
    {
        return Permission::organisatiesBeheren();
    }
}
