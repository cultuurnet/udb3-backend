<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;

final class RelocateEventToCanonicalPlace implements EventListener
{
    private CommandBus $commandBus;

    private CanonicalPlaceRepository $canonicalPlaceRepository;

    public function __construct(CommandBus $commandBus, CanonicalPlaceRepository $canonicalPlaceRepository)
    {
        $this->commandBus = $commandBus;
        $this->canonicalPlaceRepository = $canonicalPlaceRepository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof EventCreated:
                $this->handleEventCreated($event);
                break;
            case $event instanceof LocationUpdated:
                $this->handleLocationUpdated($event);
                break;
            default:
                return;
        }
    }

    private function handleEventCreated(EventCreated $event): void
    {
        $this->relocateEventToCanonicalPlace($event->getEventId(), $event->getLocation());
    }

    private function handleLocationUpdated(LocationUpdated $event): void
    {
        $this->relocateEventToCanonicalPlace($event->getItemId(), $event->getLocationId());
    }

    private function relocateEventToCanonicalPlace(string $eventId, LocationId $locationId): void
    {
        if ($locationId->isNilLocation()) {
            return;
        }

        $place = $this->canonicalPlaceRepository->findCanonicalFor($locationId->toString());
        $canonicalPlace = new LocationId($place->getAggregateRootId());
        if ($locationId->sameAs($canonicalPlace)) {
            return;
        }

        $this->commandBus->dispatch(new UpdateLocation($eventId, $canonicalPlace));
    }
}
