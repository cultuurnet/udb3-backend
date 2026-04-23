<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class BirthYearRangeDeleted extends AbstractEvent
{
    public static function deserialize(array $data): self
    {
        return new self($data['item_id']);
    }
}
