<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\Serializer\Serializable;

abstract class EventEvent implements Serializable
{
    /**
     * @var string
     */
    protected $eventId;

    public function __construct(string $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function serialize(): array
    {
        return [
            'event_id' => $this->eventId,
        ];
    }
}
