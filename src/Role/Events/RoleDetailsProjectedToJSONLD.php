<?php

namespace CultuurNet\UDB3\Role\Events;

use ValueObjects\Identity\UUID;

final class RoleDetailsProjectedToJSONLD extends AbstractEvent
{
    public static function deserialize(array $data): RoleDetailsProjectedToJSONLD
    {
        return new static(new UUID($data['uuid']));
    }
}
