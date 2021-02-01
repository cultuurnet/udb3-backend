<?php

namespace CultuurNet\UDB3\Organizer\Events;

final class OrganizerDeleted extends OrganizerEvent
{
    public static function deserialize(array $data): OrganizerDeleted
    {
        return new static($data['organizer_id']);
    }
}
