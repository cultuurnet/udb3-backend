<?php

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractOfferDeleted extends AbstractEvent
{
    final public function __construct(string $itemId)
    {
        parent::__construct($itemId);
    }

    public static function deserialize(array $data): AbstractOfferDeleted
    {
        return new static($data['item_id']);
    }
}
