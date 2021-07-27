<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractUpdateOrganizerCommand extends AbstractOrganizerCommand implements AuthorizableCommandInterface
{
    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }

    public function getPermission(): Permission
    {
        return Permission::ORGANISATIES_BEWERKEN();
    }
}
