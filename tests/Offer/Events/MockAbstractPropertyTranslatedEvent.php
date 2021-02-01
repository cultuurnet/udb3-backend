<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Language;

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
