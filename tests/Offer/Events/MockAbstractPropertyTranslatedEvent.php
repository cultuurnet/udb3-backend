<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class MockAbstractPropertyTranslatedEvent extends AbstractPropertyTranslatedEvent
{
    public static function deserialize(array $data): MockAbstractPropertyTranslatedEvent
    {
        return new self(
            $data['item_id'],
            new Language($data['language'])
        );
    }
}
