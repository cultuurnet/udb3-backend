<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractModerationCommand extends AbstractCommand
{
    public function getPermission(): Permission
    {
        return Permission::AANBOD_MODEREREN();
    }
}
