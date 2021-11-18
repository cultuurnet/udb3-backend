<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class ThemeRemoved extends AbstractEvent
{
    public static function deserialize(array $data)
    {
        return new static ($data['item_id']);
    }
}
