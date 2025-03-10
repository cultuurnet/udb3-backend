<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class RoleDeleted extends AbstractEvent
{
    public static function deserialize(array $data): RoleDeleted
    {
        return new self(new Uuid($data['uuid']));
    }
}
