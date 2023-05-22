<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

final class ExternalIdLocationUpdated
{
    private string $eventId;
    private string $externalId;

    public function __construct(string $eventId, string $externalId)
    {
        $this->eventId = $eventId;
        $this->externalId = $externalId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }
}
