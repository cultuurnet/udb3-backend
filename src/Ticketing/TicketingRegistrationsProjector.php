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

    private TicketingRegistrationRepository $repository;

    /** @var string[] */
    private array $reservationLinkHosts;

    /**
     * @param string[] $reservationLinkHosts
     */
    public function __construct(TicketingRegistrationRepository $repository, array $reservationLinkHosts)
    {
        $this->repository = $repository;
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
        return in_array(strtolower($link->getDomain()), $this->reservationLinkHosts, true);
    }
}
