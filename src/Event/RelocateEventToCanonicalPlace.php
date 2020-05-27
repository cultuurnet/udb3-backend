<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;

final class RelocateEventToCanonicalPlace implements EventListenerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var CanonicalPlaceRepository
     */
    private $canonicalPlaceRepository;

    public function __construct(CommandBusInterface $commandBus, CanonicalPlaceRepository $canonicalPlaceRepository)
    {
        $this->commandBus = $commandBus;
        $this->canonicalPlaceRepository = $canonicalPlaceRepository;
    }

    public function handle(DomainMessage $domainMessage)
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
        $place = $this->canonicalPlaceRepository->findCanonicalFor($locationId->toNative());
        $canonicalPlace = new LocationId($place->getAggregateRootId());
        if ($locationId->sameValueAs($canonicalPlace)) {
            return;
        }

        $this->commandBus->dispatch(new UpdateLocation($eventId, $canonicalPlace));
    }
}
