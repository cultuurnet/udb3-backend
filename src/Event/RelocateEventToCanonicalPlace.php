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
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;

final class RelocateEventToCanonicalPlace implements EventListener
{
    private CommandBus $commandBus;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    public function __construct(CommandBus $commandBus, DuplicatePlaceRepository $duplicatePlaceRepository)
    {
        $this->commandBus = $commandBus;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
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

        $canonicalId = $this->duplicatePlaceRepository->getCanonicalOfPlace($locationId->toString());
        if ($canonicalId === null || $canonicalId === $locationId->toString()) {
            return;
        }

        $this->commandBus->dispatch(new UpdateLocation($eventId, new LocationId($canonicalId)));
    }
}
