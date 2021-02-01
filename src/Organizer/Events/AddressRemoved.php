<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class AddressRemoved extends OrganizerEvent
{
    public static function deserialize(array $data): AddressRemoved
    {
        return new static($data['organizer_id']);
    }
}
