<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

interface TicketingRegistrationRepository
{
    /**
     * Adding a registration that already exists does nothing.
     */
    public function add(TicketingRegistration $registration): void;

    public function remove(Uuid $eventId, Url $reservationLink): void;

    public function findByEvent(Uuid $eventId): TicketingRegistrations;

    public function findByIdentifier(string $identifier): TicketingRegistrations;

    public function findWithoutIdentifier(): TicketingRegistrations;

    /**
     * Does nothing when the link was removed from the event in the meantime.
     */
    public function setIdentifier(Uuid $eventId, Url $reservationLink, string $identifier): void;
}
