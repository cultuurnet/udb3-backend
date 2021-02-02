<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class Concluded extends AbstractEvent
{
    public static function deserialize(array $data): Concluded
    {
        return new self($data['item_id']);
    }
}
