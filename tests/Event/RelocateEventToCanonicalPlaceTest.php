<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class RelocateEventToCanonicalPlaceTest extends TestCase
{
    /**
     * @var RelocateEventToCanonicalPlace
     */
    private $processManager;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var CanonicalPlaceRepository | MockObject
     */
    private $canonicalPlaceRepository;

    protected function setUp()
    {
        $this->commandBus = new TraceableCommandBus();
        $this->canonicalPlaceRepository = $this->createMock(CanonicalPlaceRepository::class);

        $this->processManager = new RelocateEventToCanonicalPlace(
            $this->commandBus,
            $this->canonicalPlaceRepository
        );
    }

    /**
     * @test
     */
    public function it_will_not_relocate_events_when_they_already_use_a_canonical_place(): void
    {
        $eventId = Uuid::generateAsString();
        $locationId = new LocationId(Uuid::generateAsString());
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn(null);
        $place->method('getAggregateRootId')->willReturn($locationId->toNative());
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId)->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(
            new EventCreated(
                $eventId,
                new Language('en'),
                new Title('Faith no More'),
                new EventType('0.50.4.0.0', 'Concert'),
                $locationId,
                new Calendar(CalendarType::PERMANENT()),
                new Theme('1.8.1.0.0', 'Rock')
            )
        );
        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_will_relocate_event_when_it_was_created_for_a_duplicate_place(): void
    {
        $eventId = Uuid::generateAsString();
        $locationId = new LocationId(Uuid::generateAsString());
        $canonicalPlaceId = Uuid::generateAsString();
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $place->method('getAggregateRootId')->willReturn($canonicalPlaceId);
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId)->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(
            new EventCreated(
                $eventId,
                new Language('en'),
                new Title('Faith no More'),
                new EventType('0.50.4.0.0', 'Concert'),
                $locationId,
                new Calendar(CalendarType::PERMANENT()),
                new Theme('1.8.1.0.0', 'Rock')
            )
        );
        $this->assertEquals(
            [
                new UpdateLocation($eventId, new LocationId($canonicalPlaceId)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_will_relocate_event_when_its_location_was_updated_to_a_duplicate_place(): void
    {
        $eventId = Uuid::generateAsString();
        $locationId = new LocationId(Uuid::generateAsString());
        $canonicalPlaceId = Uuid::generateAsString();
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $place->method('getAggregateRootId')->willReturn($canonicalPlaceId);
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId)->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(new LocationUpdated($eventId, $locationId));
        $this->assertEquals(
            [
                new UpdateLocation($eventId, new LocationId($canonicalPlaceId)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    private function broadcastNow($event)
    {
        $this->processManager->handle(
            DomainMessage::recordNow(
                UUID::generateAsString(),
                0,
                Metadata::deserialize([]),
                $event
            )
        );
    }
}
