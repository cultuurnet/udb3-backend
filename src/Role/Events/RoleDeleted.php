<?php

namespace CultuurNet\UDB3\Role\Events;

use ValueObjects\Identity\UUID;

final class RoleDeleted extends AbstractEvent
{
    public static function deserialize(array $data): RoleDeleted
    {
        return new self(new UUID($data['uuid']));
    }
}
