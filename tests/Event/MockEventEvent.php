<?php

namespace CultuurNet\UDB3\Event;

class MockEventEvent extends EventEvent
{
    final public function __construct(string $eventId)
    {
        parent::__construct($eventId);
    }

    public static function deserialize(array $data): MockEventEvent
    {
        return new self($data['event_id']);
    }
}
