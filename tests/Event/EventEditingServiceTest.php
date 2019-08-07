<?php

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Language;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\UDB3\Title;

class DefaultEventEditingServiceTest extends TestCase
{
    /**
     * @var EventEditingService
     */
    protected $eventEditingService;

    /**
     * @var EventServiceInterface|MockObject
     */
    protected $eventService;

    /**
     * @var CommandBusInterface|MockObject
     */
    protected $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    protected $uuidGenerator;

    /**
     * @var OfferCommandFactoryInterface|MockObject
     */
    protected $commandFactory;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    protected $readRepository;

    /**
     * @var RepositoryInterface|MockObject
     */
    protected $writeRepository;

    /**
     * @var Label\LabelServiceInterface|MockObject
     */
    protected $labelService;

    /**
     * @var TraceableEventStore
     */
    protected $eventStore;

    public function setUp()
    {
        $this->eventService = $this->createMock(EventServiceInterface::class);

        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);

        /** @var DocumentRepositoryInterface $repository */
        $this->readRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );

        $this->writeRepository = new EventRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->eventEditingService = new EventEditingService(
            $this->eventService,
            $this->commandBus,
            $this->uuidGenerator,
            $this->readRepository,
            $this->commandFactory,
            $this->writeRepository,
            $this->labelService
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_title_of_unknown_event()
    {
        $id = 'some-unknown-id';

        $this->expectException(DocumentGoneException::class);

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

        $this->expectException(DocumentGoneException::class);

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
    public function it_refuses_to_label_an_unknown_event()
    {
        $id = 'some-unknown-id';

        $this->expectException(DocumentGoneException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->addLabel($id, new Label('foo'));
    }

    /**
     * @test
     */
    public function it_refuses_to_remove_a_label_from_an_unknown_event()
    {
        $id = 'some-unknown-id';

        $this->expectException(DocumentGoneException::class);

        $this->setUpEventNotFound($id);

        $this->eventEditingService->RemoveLabel($id, new Label('foo'));
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
            \DateTime::ATOM,
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
    public function it_can_dispatch_an_update_calendar_command()
    {
        $eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $updateCalendar = new UpdateCalendar($eventId, $calendar);

        $expectedCommandId = 'commandId';

        $this->commandFactory->expects($this->once())
            ->method('createUpdateCalendarCommand')
            ->with($eventId, $calendar)
            ->willReturn($updateCalendar);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateCalendar)
            ->willReturn($expectedCommandId);

        $this->readRepository->expects($this->once())
            ->method('get')
            ->with($eventId)
            ->willReturn(new JsonDocument($eventId));

        $commandId = $this->eventEditingService->updateCalendar($eventId, $calendar);

        $this->assertEquals($expectedCommandId, $commandId);
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

    /**
     * @test
     */
    public function it_can_dispatch_an_update_location_command()
    {
        $eventId = '3ed90f18-93a3-4340-981d-12e57efa0211';

        $locationId = new LocationId('57738178-28a5-4afb-90c0-fd0beba172a8');

        $updateLocation = new UpdateLocation($eventId, $locationId);

        $expectedCommandId = 'commandId';

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateLocation)
            ->willReturn($expectedCommandId);

        $this->readRepository->expects($this->once())
            ->method('get')
            ->with($eventId)
            ->willReturn(new JsonDocument($eventId));

        $commandId = $this->eventEditingService->updateLocation($eventId, $locationId);

        $this->assertEquals($expectedCommandId, $commandId);
    }

    /**
     * @param mixed $id
     */
    private function setUpEventNotFound($id)
    {
        $this->readRepository->expects($this->once())
            ->method('get')
            ->with($id)
            ->willThrowException(new DocumentGoneException());
    }
}
