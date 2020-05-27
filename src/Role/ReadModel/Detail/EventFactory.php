<?php

namespace CultuurNet\UDB3\Role\ReadModel\Detail;

use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;
use ValueObjects\Identity\UUID;

class EventFactory implements DocumentEventFactory
{
    /**
     * @param string $id
     * @return RoleDetailsProjectedToJSONLD
     */
    public function createEvent($id)
    {
        return new RoleDetailsProjectedToJSONLD(new UUID($id));
    }
}
