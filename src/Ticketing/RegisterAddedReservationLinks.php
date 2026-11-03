<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Event\Events\ReservationLinkAdded;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;

/**
 * Registers every new reservation link on a ticketing host with its ticket sales provider. Only subscribed through a
 * ReplayFilteringEventListener, so a replay never calls the provider again.
 */
final class RegisterAddedReservationLinks implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /** @var string[] */
    private readonly array $reservationLinkHosts;

    /**
     * @param string[] $reservationLinkHosts
     */
    public function __construct(
        private readonly CommandBus $commandBus,
        array $reservationLinkHosts
    ) {
        $this->reservationLinkHosts = array_map('strtolower', $reservationLinkHosts);
    }

    protected function applyReservationLinkAdded(ReservationLinkAdded $reservationLinkAdded): void
    {
        $host = strtolower($reservationLinkAdded->reservationLink->getDomain());
        if (!in_array($host, $this->reservationLinkHosts, true)) {
            return;
        }

        $this->commandBus->dispatch(
            new RegisterReservationLink($reservationLinkAdded->getItemId(), $reservationLinkAdded->reservationLink)
        );
    }
}
