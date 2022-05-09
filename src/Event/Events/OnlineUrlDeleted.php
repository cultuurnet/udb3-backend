<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use Broadway\Serializer\Serializable;

final class OnlineUrlDeleted implements Serializable
{
    private string $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public static function deserialize(array $data): OnlineUrlDeleted
    {
        return new OnlineUrlDeleted($data['eventId']);
    }

    public function serialize(): array
    {
        return [
            'eventId' => $this->eventId,
        ];
    }
}
