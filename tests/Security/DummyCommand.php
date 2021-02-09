<?php

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class DummyCommand implements AuthorizableCommandInterface
{
    public function getItemId()
    {
        return '';
    }

    public function getPermission()
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
