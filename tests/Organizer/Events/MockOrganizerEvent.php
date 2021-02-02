<?php

namespace CultuurNet\UDB3\Organizer\Events;

final class MockOrganizerEvent extends OrganizerEvent
{
    public static function deserialize(array $data): MockOrganizerEvent
    {
        return new static($data['organizer_id']);
    }
}
