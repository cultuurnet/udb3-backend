<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class TypicalBirthDateDeleted extends AbstractEvent
{
    public function __construct(string $itemId)
    {
        parent::__construct($itemId);
    }

    public static function deserialize(array $data): TypicalBirthDateDeleted
    {
        return new self($data['item_id']);
    }
}
