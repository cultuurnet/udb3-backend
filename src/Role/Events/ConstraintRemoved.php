<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use ValueObjects\Identity\UUID;

final class ConstraintRemoved extends AbstractEvent
{
    public static function deserialize(array $data): ConstraintRemoved
    {
        return new ConstraintRemoved(
            new UUID($data['uuid']),
        );
    }
}
