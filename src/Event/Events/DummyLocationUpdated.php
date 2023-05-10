<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\ValueObjects\DummyLocation;

final class DummyLocationUpdated
{
    private string $eventId;
    private DummyLocation $dummyLocation;

    public function __construct(string $eventId, DummyLocation $dummyLocation)
    {
        $this->eventId = $eventId;
        $this->dummyLocation = $dummyLocation;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getDummyLocation(): DummyLocation
    {
        return $this->dummyLocation;
    }
}
