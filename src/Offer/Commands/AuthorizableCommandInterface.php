<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

interface AuthorizableCommandInterface
{
    public function getItemId(): string;

    public function getPermission(): Permission;
}
