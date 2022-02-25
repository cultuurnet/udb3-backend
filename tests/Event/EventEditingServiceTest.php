<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Location\LocationNotFound;
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
use CultuurNet\UDB3\StringLiteral;

final class EventEditingServiceTest extends TestCase
{
    private EventEditingService $eventEditingService;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var DocumentRepository|MockObject
     */
    private $readRepository;


    private TraceableEventStore $eventStore;

    /**
     * @var PlaceRepository|MockObject
     */
    private $placeRepository;

    protected function setUp(): void
    {
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->readRepository = $this->createMock(DocumentRepository::class);
        $this->placeRepository = $this->createMock(PlaceRepository::class);

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );

        $this->eventEditingService = new EventEditingService(
            $this->createMock(CommandBus::class),
            $this->uuidGenerator,
            $this->readRepository,
            $this->createMock(OfferCommandFactoryInterface::class),
            new EventRepository($this->eventStore, new SimpleEventBus()),
            $this->placeRepository
        );
    }

    /**
     * @test
     */
    public function it_refuses_to_update_title_of_unknown_event(): void
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
    public function it_refuses_to_update_the_description_of_unknown_event(): void
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
    public function it_can_create_a_new_event(): void
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('1f33fff3-d975-4718-a065-95d10e6ab4f2');
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
    public function it_should_be_able_to_create_a_new_event_and_approve_it_immediately(): void
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('5825b862-49b7-4d45-bccf-9fa01d2fec7f');
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
        $invalidLocation = new LocationId('742ff804-2246-4c60-993b-a01967dac2c4');
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
        $invalidLocation = new LocationId('783b2905-601e-4ef0-b9ac-88e59dd37a8a');
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
    public function it_can_create_a_new_event_with_a_fixed_publication_date(): void
    {
        $eventId = 'generated-uuid';
        $mainLanguage = new Language('nl');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $location = new LocationId('45a6c3fa-465a-43c6-89bd-cced3765d850');
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

    private function setUpEventNotFound($id): void
    {
        $this->readRepository->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willThrowException(DocumentDoesNotExist::withId($id));
    }
}
