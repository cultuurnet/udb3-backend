<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

final class MockAbstractEvent extends AbstractEvent
{
    public static function deserialize(array $data): MockAbstractEvent
    {
        return new self($data['item_id']);
    }
}
