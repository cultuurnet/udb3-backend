<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Ticketing\Providers\InvalidReservationLink;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkNotFound;
use CultuurNet\UDB3\Ticketing\Providers\TicketingProvider;
use Throwable;
use Psr\Log\LoggerInterface;

final class RegisterReservationLinkHandler implements CommandHandler
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly TicketingProvider $ticketingProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle($command): void
    {
        if (!$command instanceof RegisterReservationLink) {
            return;
        }

        $context = [
            'event_id' => $command->eventId,
            'reservation_link' => $command->reservationLink->toString(),
        ];

        // A failing lookup at the provider doesn't fail the change that added the link. The link stays without
        // identifier, so it can be registered again later. Errors while saving the registration itself are not caught,
        // those are bugs or conflicts that RetryingCommandBus handles.
        try {
            $bookingInfo = $this->ticketingProvider->getBookingInfo($command->reservationLink);
        } catch (ReservationLinkNotFound $exception) {
            $this->logger->info('Reservation link not (yet) known by the ticket sales provider', $context);
            return;
        } catch (InvalidReservationLink $exception) {
            $this->logger->warning('Reservation link rejected by the ticket sales provider', $context);
            return;
        } catch (Throwable $exception) {
            $this->logger->error('Could not register reservation link', $context + ['exception' => $exception]);
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->eventId);

        $event->registerReservationLink(
            $command->reservationLink,
            $bookingInfo->identifier,
            $bookingInfo->bookingAvailability->withWaitingListUrl($bookingInfo->waitingListUrl)
        );

        $this->eventRepository->save($event);
    }
}
