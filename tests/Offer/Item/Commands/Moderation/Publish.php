<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item\Commands\Moderation;

use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractPublish;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class Publish extends AbstractPublish
{
    public function getPermission(): Permission
    {
        return Permission::aanbodBewerken();
    }
}
