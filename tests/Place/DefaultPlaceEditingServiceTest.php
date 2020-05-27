<?php

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateCalendar;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class DefaultPlaceEditingServiceTest extends TestCase
{

    /**
     * @var DefaultPlaceEditingService
     */
    protected $placeEditingService;

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
     * @var LabelServiceInterface|MockObject
     */
    protected $labelService;

    /**
     * @var TraceableEventStore
     */
    protected $eventStore;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->uuidGenerator = $this->createMock(
            UuidGeneratorInterface::class
        );

        $this->commandFactory = $this->createMock(OfferCommandFactoryInterface::class);

        /** @var DocumentRepositoryInterface $repository */
        $this->readRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );
        $this->writeRepository = new PlaceRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->readRepository->expects($this->any())
            ->method('get')
            ->with('ad93103d-1395-4af7-a52a-2829d466c232')
            ->willReturn(new JsonDocument('ad93103d-1395-4af7-a52a-2829d466c232'));

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->placeEditingService = new DefaultPlaceEditingService(
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
    public function it_can_create_a_new_place()
    {
        $street = new Street('Kerkstraat 69');
        $locality = new Locality('Leuven');
        $postalCode = new PostalCode('3000');
        $country = Country::fromNative('BE');

        $placeId = 'generated-uuid';
        $mainLanguage = new Language('en');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $address = new Address($street, $postalCode, $locality, $country);
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->eventStore->trace();

        $this->placeEditingService->createPlace(
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new PlaceCreated(
                    $placeId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $address,
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
    public function it_should_be_able_to_create_a_new_place_and_approve_it_immediately()
    {
        $street = new Street('Kerkstraat 69');
        $locality = new Locality('Leuven');
        $postalCode = new PostalCode('3000');
        $country = Country::fromNative('BE');

        $placeId = 'generated-uuid';
        $mainLanguage = new Language('en');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $address = new Address($street, $postalCode, $locality, $country);
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $publicationDate = new \DateTimeImmutable();
        $service = $this->placeEditingService->withFixedPublicationDateForNewOffers($publicationDate);

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('generated-uuid');

        $this->eventStore->trace();

        $service->createApprovedPlace(
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new PlaceCreated(
                    $placeId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $address,
                    $calendar,
                    $theme
                ),
                new Published($placeId, $publicationDate),
                new Approved($placeId),
            ],
            $this->eventStore->getEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_new_place_with_a_fixed_publication_date()
    {
        $publicationDate = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2016-08-01T00:00:00+0200');

        $street = new Street('Kerkstraat 69');
        $locality = new Locality('Leuven');
        $postalCode = new PostalCode('3000');
        $country = Country::fromNative('BE');
        $placeId = 'generated-uuid';
        $mainLanguage = new Language('en');
        $title = new Title('Title');
        $eventType = new EventType('0.50.4.0.0', 'concert');
        $address = new Address($street, $postalCode, $locality, $country);
        $calendar = new Calendar(CalendarType::PERMANENT());
        $theme = null;

        $this->uuidGenerator->expects($this->once())
          ->method('generate')
          ->willReturn('generated-uuid');

        $this->eventStore->trace();

        $editingService = $this->placeEditingService->withFixedPublicationDateForNewOffers($publicationDate);

        $editingService->createPlace(
            $mainLanguage,
            $title,
            $eventType,
            $address,
            $calendar,
            $theme
        );

        $this->assertEquals(
            [
                new PlaceCreated(
                    $placeId,
                    $mainLanguage,
                    $title,
                    $eventType,
                    $address,
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
        $placeId = 'ad93103d-1395-4af7-a52a-2829d466c232';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $updateCalendar = new UpdateCalendar($placeId, $calendar);

        $expectedCommandId = 'commandId';

        $this->commandFactory->expects($this->once())
            ->method('createUpdateCalendarCommand')
            ->with($placeId, $calendar)
            ->willReturn($updateCalendar);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateCalendar)
            ->willReturn($expectedCommandId);

        $commandId = $this->placeEditingService->updateCalendar($placeId, $calendar);

        $this->assertEquals($expectedCommandId, $commandId);
    }

    /**
     * @test
     */
    public function it_should_update_the_address_of_a_place_by_dispatching_a_relevant_command()
    {
        $id = 'ad93103d-1395-4af7-a52a-2829d466c232';
        $address = new Address(
            new Street('Eenmeilaan 35'),
            new PostalCode('3010'),
            new Locality('Kessel-Lo'),
            Country::fromNative('BE')
        );
        $language = new Language('nl');

        $expectedCommandId = '98994a85-f0d9-4862-a91e-02f116bd609b';

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new UpdateAddress($id, $address, $language))
            ->willReturn($expectedCommandId);

        $actualCommandId = $this->placeEditingService->updateAddress($id, $address, $language);

        $this->assertEquals($expectedCommandId, $actualCommandId);
    }
}
