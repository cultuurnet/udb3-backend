<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Title;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class EventEditingServiceTest extends TestCase
{
    /**
     * @var EventEditingService
     */
    private $eventEditingService;

    /**
     * @var EventServiceInterface|MockObject
     */
    private $eventService;

    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    private $commandFactory;

    /**
     * @var DocumentRepository|MockObject
     */
    private $readRepository;

    /**
     * @var Repository|MockObject
     */
    private $writeRepository;

    /**
     * @var TraceableEventStore
     */
    private $eventStore;

    /**
     * @var PlaceRepository|MockObject
     */
    private $placeRepository;

    protected function setUp()
    {
        $this->eventService = $this->createMock(EventServiceInterface::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);
        $this->readRepository = $this->createMock(DocumentRepository::class);
        $this->placeRepository = $this->createMock(PlaceRepository::class);

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );

        $this->writeRepository = new EventRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->eventEditingService = new EventEditingService(
            $this->eventService,
            $this->commandBus,
            $this->uuidGenerator,
            $this->readRepository,
            $this->commandFactory,
            $this->writeRepository,
            $this->placeRepository
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_title_of_unknown_event()
    {
        $id = 'some-unknown-id';

        $this->expectException(EntityNotFoundException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->updateTitle(
            $id,
            new Language('nl'),
            new StringLiteral('new title')
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_the_description_of_unknown_event()
    {
        $id = 'some-unknown-id';

        $this->expectException(EntityNotFoundException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->updateDescription(
            $id,
            new Language('en'),
            new Description('new description')
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_new_event()
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId(UUID::generateAsString());
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->eventEditingService->createEvent(
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new EventCreated(
                    $eventId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $location,
                    $calendar,
                    $theme
                ),
            ],
            $this->eventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_should_be_able_to_create_a_new_event_and_approve_it_immediately()
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId(UUID::generateAsString());
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $publicationDate = new \DateTimeImmutable();
        $service = $this->eventEditingService->withFixedPublicationDateForNewOffers(
            $publicationDate
        );

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $service->createApprovedEvent(
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new EventCreated(
                    $eventId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $location,
                    $calendar,
                    $theme
                ),
                new Published($eventId, $publicationDate),
                new Approved($eventId),
            ],
            $this->eventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_will_not_create_and_event_when_location_cannot_be_found(): void
    {
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $invalidLocation = new LocationId(UUID::generateAsString());
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->placeRepository->method('load')
            ->with($invalidLocation->toNative())
            ->willThrowException(new AggregateNotFoundException());

        $this->expectException(LocationNotFound::class);

        $this->eventEditingService->createEvent(
            $mainLanguage,
            $title,
            $eventType,
            $invalidLocation,
            $calendar,
            $theme
        );
    }

    /**
     * @test
     */
    public function it_will_not_create_and_automatically_approve_an_event_when_location_cannot_be_found(): void
    {
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $invalidLocation = new LocationId(UUID::generateAsString());
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->placeRepository->method('load')
            ->with($invalidLocation->toNative())
            ->willThrowException(new AggregateNotFoundException());

        $this->expectException(LocationNotFound::class);

        $this->eventEditingService->createApprovedEvent(
            $mainLanguage,
            $title,
            $eventType,
            $invalidLocation,
            $calendar,
            $theme
        );
    }

    /**
     * @test
     */
    public function it_can_copy_an_existing_event()
    {
        $eventId = 'e49430ca-5729-4768-8364-02ddb385517a';
        $originalEventId = '27105ae2-7e1c-425e-8266-4cb86a546159';
        $calendar = new Calendar(CalendarType::PERMANENT());

        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId(UUID::generateAsString());
        $theme = null;

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls($originalEventId, $eventId);

        $this->eventEditingService->createEvent(
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $this->eventEditingService->copyEvent($originalEventId, $calendar);

        $this->assertEquals(
            [
                new EventCreated(
                    $originalEventId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $location,
                    $calendar,
                    $theme
                ),
                new EventCopied(
                    $eventId,
                    $originalEventId,
                    $calendar
                ),
            ],
            $this->eventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_invalid_argument_exception_during_copy_when_type_mismatch_for_original_event_id()
    {
        $originalEventId = '27105ae2-7e1c-425e-8266-4cb86a546159';
        $calendar = new Calendar(CalendarType::PERMANENT());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No original event found to copy with id ' . $originalEventId
        );

        $this->eventEditingService->copyEvent($originalEventId, $calendar);
    }

    /**
     * @test
     */
    public function it_throws_an_invalid_argument_exception_during_copy_when_original_event_is_missing()
    {
        $originalEventId = false;
        $calendar = new Calendar(CalendarType::PERMANENT());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected originalEventId to be a string, received bool'
        );

        $this->eventEditingService->copyEvent($originalEventId, $calendar);
    }

    /**
     * @test
     */
    public function it_can_create_a_new_event_with_a_fixed_publication_date()
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId(UUID::generateAsString());
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;
        $publicationDate = \DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            '2016-08-01T00:00:00+00:00'
        );

        $this->eventEditingService = $this->eventEditingService
            ->withFixedPublicationDateForNewOffers($publicationDate);

        $this->eventStore->trace();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->eventEditingService->createEvent(
            $mainLanguage,
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new EventCreated(
                    $eventId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $location,
                    $calendar,
                    $theme,
                    $publicationDate
                ),
            ],
            $this->eventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_dispatch_an_update_audience_command()
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $audience = new Audience(AudienceType::EDUCATION());

        $expectedCommandId = 'commandId';

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new UpdateAudience($eventId, $audience))
            ->willReturn($expectedCommandId);

        $commandId = $this->eventEditingService->updateAudience($eventId, $audience);

        $this->assertEquals($expectedCommandId, $commandId);
    }

    private function setUpEventNotFound($id)
    {
        $this->readRepository->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willThrowException(DocumentDoesNotExist::withId($id));
    }
}
