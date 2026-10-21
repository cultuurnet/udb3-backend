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

    public function remove(Uuid $eventId, Url $reservationLink, RegistrationSource $source): void;

    public function removeAllOfEvent(Uuid $eventId): void;

    public function findByEvent(Uuid $eventId, RegistrationSource $source): TicketingRegistrations;

    public function findByIdentifier(string $identifier): TicketingRegistrations;

    public function findWithoutIdentifier(): TicketingRegistrations;

    /**
     * Sets the identifier on every row of the event with this link, on the top level and on its subEvents.
     * Does nothing when the link was removed from the event in the meantime.
     */
    public function setIdentifier(Uuid $eventId, Url $reservationLink, string $identifier): void;
}
