<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class RoleDetailsProjectedToJSONLD extends AbstractEvent
{
    public static function deserialize(array $data): RoleDetailsProjectedToJSONLD
    {
        return new static(new UUID($data['uuid']));
    }
}
