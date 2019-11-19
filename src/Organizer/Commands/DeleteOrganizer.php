<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class DeleteOrganizer extends AbstractOrganizerCommand implements AuthorizableCommandInterface
{
    /**
     * @inheritdoc
     */
    public function getItemId()
    {
        return $this->getOrganizerId();
    }

    /**
     * @inheritdoc
     */
    public function getPermission()
    {
        return Permission::ORGANISATIES_BEHEREN();
    }
}
