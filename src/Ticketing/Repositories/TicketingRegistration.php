<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class TicketingRegistration
{
    private Uuid $eventId;
    private Url $reservationLink;
    private RegistrationSource $source;
    private ?string $identifier;

    public function __construct(
        Uuid $eventId,
        Url $reservationLink,
        RegistrationSource $source,
        ?string $identifier = null
    ) {
        $this->eventId = $eventId;
        $this->reservationLink = $reservationLink;
        $this->source = $source;
        $this->identifier = $identifier;
    }

    public function getEventId(): Uuid
    {
        return $this->eventId;
    }

    public function getReservationLink(): Url
    {
        return $this->reservationLink;
    }

    public function getSource(): RegistrationSource
    {
        return $this->source;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }
}
