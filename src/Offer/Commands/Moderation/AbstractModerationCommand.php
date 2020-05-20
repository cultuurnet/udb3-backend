<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractModerationCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    public function getPermission()
    {
        return Permission::AANBOD_MODEREREN();
    }
}
