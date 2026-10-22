<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\ReservationLinkAdded;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;

/**
 * Keeps the reservation links of ticket sales providers in ticketing_registrations, so availability updates from the
 * provider can find the events with that link. Links on other hosts are ordinary booking links and are ignored.
 */
final class TicketingRegistrationsProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    // The length of ticketing_registrations.reservation_link. Ticketmatic links are far shorter.
    private const MAX_LINK_LENGTH = 512;

    /** @var string[] */
    private readonly array $reservationLinkHosts;

    /**
     * @param string[] $reservationLinkHosts
     */
    public function __construct(
        private readonly TicketingRegistrationRepository $repository,
        array $reservationLinkHosts
    ) {
        $this->reservationLinkHosts = array_map('strtolower', $reservationLinkHosts);
    }

    protected function applyReservationLinkAdded(ReservationLinkAdded $reservationLinkAdded): void
    {
        if (!$this->isTicketingLink($reservationLinkAdded->reservationLink)) {
            return;
        }

        $this->repository->add(
            new TicketingRegistration(
                new Uuid($reservationLinkAdded->getItemId()),
                $reservationLinkAdded->reservationLink
            )
        );
    }

    private function isTicketingLink(Url $link): bool
    {
        return in_array(strtolower($link->getDomain()), $this->reservationLinkHosts, true)
            && strlen($link->toString()) <= self::MAX_LINK_LENGTH;
    }
}
