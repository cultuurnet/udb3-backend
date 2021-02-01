<?php

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractTypicalAgeRangeDeleted extends AbstractEvent
{
    final public function __construct(string $itemId)
    {
        parent::__construct($itemId);
    }

    public static function deserialize(array $data): AbstractTypicalAgeRangeDeleted
    {
        return new static($data['item_id']);
    }
}
