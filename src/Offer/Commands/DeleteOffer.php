<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class DeleteOffer extends AbstractCommand
{
    public function getPermission(): Permission
    {
        return Permission::aanbodVerwijderen();
    }
}
