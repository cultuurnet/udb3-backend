<?php

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectorTest extends TestCase
{
    const CDBXML_NAMESPACE_33 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @var RepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var Projector
     */
    private $projector;

    public function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);

        $this->projector = new Projector(
            $this->repository,
            new EventCdbIdExtractor()
        );
    }

    /**
     * @test
     * @dataProvider cdbXmlDataProvider
     *
     * @param string $aggregateId
     * @param mixed $event
     * @param string $expectedEventId
     * @param string $expectedPlaceId
     * @param string $expectedOrganizerId
     */
    public function it_stores_relations_when_creating_or_updating_events_from_udb2_or_cdbxml(
        $aggregateId,
        $event,
        $expectedEventId,
        $expectedPlaceId,
        $expectedOrganizerId
    ) {
        $this->repository
            ->expects($this->once())
            ->method('storeRelations')
            ->with(
                $this->equalTo($expectedEventId),
                $this->equalTo($expectedPlaceId),
                $this->equalTo($expectedOrganizerId)
            );

        $dateTime = '2015-03-01T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $aggregateId,
            1,
            new Metadata(),
            $event,
            DateTime::fromString($dateTime)
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @return array
     */
    public function cdbXmlDataProvider()
    {
        $withNone = file_get_contents(__DIR__ . '/event_without_placeid_and_without_organiserid.xml');
        $withPlace = file_get_contents(__DIR__ . '/event_with_placeid_and_without_organiserid.xml');
        $withBoth = file_get_contents(__DIR__ . '/event_with_placeid_and_organiserid.xml');

        return [
            [
                'aggregateId' => 'foo',
                'event' => new EventImportedFromUDB2(
                    'foo',
                    $withNone,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => null,
                'expectedOrganizerId' => null,
            ],
            [
                'aggregateId' => 'foo',
                'event' => new EventImportedFromUDB2(
                    'foo',
                    $withPlace,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => 'bcb983d2-ffba-457d-a023-a821aa841fba',
                'expectedOrganizerId' => null,
            ],
            [
                'aggregateId' => 'foo',
                'event' => new EventImportedFromUDB2(
                    'foo',
                    $withBoth,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => 'bcb983d2-ffba-457d-a023-a821aa841fba',
                'expectedOrganizerId' => 'test-de-bijloke',
            ],
            [
                'aggregateId' => 'foo',
                'event' => new EventUpdatedFromUDB2(
                    'foo',
                    $withNone,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => null,
                'expectedOrganizerId' => null,
            ],
            [
                'aggregateId' => 'foo',
                'event' => new EventUpdatedFromUDB2(
                    'foo',
                    $withPlace,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => 'bcb983d2-ffba-457d-a023-a821aa841fba',
                'expectedOrganizerId' => null,
            ],
            [
                'aggregateId' => 'foo',
                'event' => new EventUpdatedFromUDB2(
                    'foo',
                    $withBoth,
                    self::CDBXML_NAMESPACE_33
                ),
                'expectedEventId' => 'foo',
                'expectedPlaceId' => 'bcb983d2-ffba-457d-a023-a821aa841fba',
                'expectedOrganizerId' => 'test-de-bijloke',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_stores_the_organizer_relation_when_the_organizer_of_an_event_is_updated()
    {
        $eventId = 'event-id';
        $organizerId = 'organizer-id';
        $organizerUpdatedEvent = new OrganizerUpdated($eventId, $organizerId);

        $this->repository
            ->expects($this->once())
            ->method('storeOrganizer')
            ->with(
                $this->equalTo($eventId),
                $this->equalTo($organizerId)
            );

        $domainMessage = new DomainMessage(
            $organizerUpdatedEvent->getItemId(),
            1,
            new Metadata(),
            $organizerUpdatedEvent,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_stores_the_organizer_relation_when_the_organizer_is_removed_from_an_event()
    {
        $eventId = 'event-id';
        $organizerId = 'organizer-id';
        $organizerDeletedEvent = new OrganizerDeleted($eventId, $organizerId);

        $this->repository
            ->expects($this->once())
            ->method('storeOrganizer')
            ->with(
                $this->equalTo($eventId),
                null
            );

        $domainMessage = new DomainMessage(
            $organizerDeletedEvent->getItemId(),
            1,
            new Metadata(),
            $organizerDeletedEvent,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_stores_related_place_and_organizer_from_original_event_on_copy()
    {
        $originalEventId = 'e7b5d985-9f35-4d2f-bd0f-4f5ddf7ce2f6';
        $eventId = 'dcfe65ea-c5a3-4ee3-ab75-1973aecc2cba';
        $placeId = '13096071-d1c7-476e-856f-ea8f90d13c59';
        $organizerId = '1104bad0-21a1-47a1-9642-a55898fb4735';

        $this->repository->expects($this->once())
            ->method('getPlaceOfEvent')
            ->with($originalEventId)
            ->willReturn($placeId);

        $this->repository->expects($this->once())
            ->method('getOrganizerOfEvent')
            ->with($originalEventId)
            ->willReturn($organizerId);

        $this->repository->expects($this->once())
            ->method('storeRelations')
            ->with($eventId, $placeId, $organizerId);

        $eventCopied = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::PERMANENT())
        );

        $domainMessage = new DomainMessage(
            $eventCopied->getItemId(),
            1,
            new Metadata(),
            $eventCopied,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_stores_the_location_relation_when_the_place_of_an_event_is_updated()
    {
        $eventId = 'event-id';
        $locationId = 'location-id';
        $organizerUpdatedEvent = new LocationUpdated($eventId, new LocationId($locationId));

        $this->repository
            ->expects($this->once())
            ->method('storePlace')
            ->with(
                $this->equalTo($eventId),
                $this->equalTo($locationId)
            );

        $domainMessage = new DomainMessage(
            $organizerUpdatedEvent->getItemId(),
            1,
            new Metadata(),
            $organizerUpdatedEvent,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_stores_the_location_relation_when_the_place_of_an_event_is_updated_via_a_major_info_update()
    {
        $eventId = 'event-id';
        $locationId = 'location-id';
        $majorInfoUpdatedEvent = new MajorInfoUpdated(
            $eventId,
            new Title('Test'),
            new EventType('0.1.1.1', 'Test label'),
            new LocationId($locationId),
            new Calendar(
                CalendarType::PERMANENT()
            )
        );

        $this->repository
            ->expects($this->once())
            ->method('storePlace')
            ->with(
                $this->equalTo($eventId),
                $this->equalTo($locationId)
            );

        $domainMessage = new DomainMessage(
            $majorInfoUpdatedEvent->getItemId(),
            1,
            new Metadata(),
            $majorInfoUpdatedEvent,
            DateTime::now()
        );

        $this->projector->handle($domainMessage);
    }
}
