<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelocateEventToCanonicalPlaceTest extends TestCase
{
    private RelocateEventToCanonicalPlace $processManager;

    private TraceableCommandBus $commandBus;

    /**
     * @var CanonicalPlaceRepository&MockObject
     */
    private $canonicalPlaceRepository;

    protected function setUp(): void
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
        $eventId = '0317e74b-62fd-45c7-a5c2-cb5ffacac042';
        $locationId = new LocationId('facccc5f-beac-496d-9cde-09c65608144b');
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn(null);
        $place->method('getAggregateRootId')->willReturn($locationId->toString());
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId->toString())->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(
            new EventCreated(
                $eventId,
                new Language('en'),
                'Faith no More',
                new EventType('0.50.4.0.0', 'Concert'),
                $locationId,
                new Calendar(CalendarType::permanent()),
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
        $eventId = '6e5fde00-7320-4601-a5da-811e387d9cfd';
        $locationId = new LocationId('4fc598b7-fba1-4f86-80cb-093b82112085');
        $canonicalPlaceId = 'fbe65c44-1925-4b6e-9ae7-f5491718f997';
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $place->method('getAggregateRootId')->willReturn($canonicalPlaceId);
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId->toString())->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(
            new EventCreated(
                $eventId,
                new Language('en'),
                'Faith no More',
                new EventType('0.50.4.0.0', 'Concert'),
                $locationId,
                new Calendar(CalendarType::permanent()),
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
        $eventId = 'cc70de68-c08f-44b4-b78c-5d9330d14eba';
        $locationId = new LocationId('1c82a6b7-f3ff-4a8a-adfa-a918cb490949');
        $canonicalPlaceId = '17ce529d-bf7c-4ae4-9cac-365add2ea4c8';
        $place = $this->createMock(Place::class);
        $place->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $place->method('getAggregateRootId')->willReturn($canonicalPlaceId);
        $this->canonicalPlaceRepository->method('findCanonicalFor')->with($locationId->toString())->willReturn($place);

        $this->commandBus->record();
        $this->broadcastNow(new LocationUpdated($eventId, $locationId));
        $this->assertEquals(
            [
                new UpdateLocation($eventId, new LocationId($canonicalPlaceId)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    private function broadcastNow(Serializable $event): void
    {
        $this->processManager->handle(
            DomainMessage::recordNow(
                'cf9088ea-548c-45d9-9b00-73fc04e08e71',
                0,
                Metadata::deserialize([]),
                $event
            )
        );
    }
}
