<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;

class EventFactory implements DocumentEventFactory
{
    public function createEvent(string $id): RoleDetailsProjectedToJSONLD
    {
        return new RoleDetailsProjectedToJSONLD(new UUID($id));
    }
}
