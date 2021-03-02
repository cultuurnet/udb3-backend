<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use ValueObjects\Identity\UUID;

class EventFactory implements DocumentEventFactory
{
    public function createEvent(string $id): RoleDetailsProjectedToJSONLD
    {
        return new RoleDetailsProjectedToJSONLD(new UUID($id));
    }
}
