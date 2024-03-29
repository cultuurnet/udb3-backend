<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface AuthorizableCommand
{
    public function getItemId(): string;

    public function getPermission(): Permission;
}
