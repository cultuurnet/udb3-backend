<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

// Still defined inside the code to make sure the event store can be loaded
final class Concluded extends AbstractEvent
{
    public static function deserialize(array $data): Concluded
    {
        return new self($data['item_id']);
    }
}
