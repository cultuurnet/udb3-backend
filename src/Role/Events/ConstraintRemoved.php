<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class ConstraintRemoved extends AbstractEvent
{
    public static function deserialize(array $data): ConstraintRemoved
    {
        return new ConstraintRemoved(
            new Uuid($data['uuid']),
        );
    }
}
