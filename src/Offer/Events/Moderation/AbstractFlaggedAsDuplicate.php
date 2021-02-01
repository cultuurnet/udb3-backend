<?php

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractFlaggedAsDuplicate extends AbstractEvent
{
    final public function __construct(string $itemId)
    {
        parent::__construct($itemId);
    }

    public static function deserialize(array $data): AbstractFlaggedAsDuplicate
    {
        return new static($data['item_id']);
    }
}
