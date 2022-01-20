<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

class DummyCommand implements AuthorizableCommand
{
    public function getItemId(): string
    {
        return '';
    }

    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
