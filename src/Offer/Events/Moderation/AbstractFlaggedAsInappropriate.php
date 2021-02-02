<?php

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractFlaggedAsInappropriate extends AbstractEvent
{
    final public function __construct(string $itemId)
    {
        parent::__construct($itemId);
    }

    public static function deserialize(array $data): AbstractFlaggedAsInappropriate
    {
        return new static($data['item_id']);
    }
}
