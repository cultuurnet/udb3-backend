<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateBookingAvailabilityByReservationLink;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class UpdateBookingAvailabilityByReservationLinkHandler implements CommandHandler
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateBookingAvailabilityByReservationLink) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->eventId);

        $event->updateBookingAvailabilityByReservationLink($command->reservationLink, $command->bookingAvailability);

        $this->eventRepository->save($event);
    }
}
