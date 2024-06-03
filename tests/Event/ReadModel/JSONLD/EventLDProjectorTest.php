<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Cdb\CdbXmlPriceInfoParser;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Completeness\CompletenessFromWeights;
use CultuurNet\UDB3\Completeness\Weights;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Calendar\CalendarFactory;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractor;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\CdbXMLEventFactory;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\OfferLDProjectorTestBase;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Calendar\Timestamp;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class EventLDProjectorTest extends OfferLDProjectorTestBase
{
    public const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @var LocalPlaceService|MockObject
     */
    private $placeService;

    private CdbXMLEventFactory $cdbXMLEventFactory;

    protected MediaObjectSerializer $serializer;

    /**
     * @var IriOfferIdentifierFactoryInterface|MockObject
     */
    protected $iriOfferIdentifierFactory;

    /**
     * @var CdbXMLImporter|MockObject
     */
    protected $cdbXMLImporter;

    public function __construct(string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName, 'CultuurNet\\UDB3\\Event');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cdbXMLEventFactory = new CdbXMLEventFactory();

        $this->placeService = $this->createMock(LocalPlaceService::class);

        $iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->serializer = new MediaObjectSerializer($iriGenerator);

        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);
        $this->cdbXMLImporter = new CdbXMLImporter(
            new CdbXMLItemBaseImporter(
                new CdbXmlPriceInfoParser(
                    new PriceDescriptionParser(
                        new NumberFormatRepository(),
                        new CurrencyRepository()
                    )
                ),
                [
                    'nl' => 'Basistarief',
                    'fr' => 'Tarif de base',
                    'en' => 'Base tarif',
                    'de' => 'Basisrate',
                ]
            ),
            new EventCdbIdExtractor(),
            new CalendarFactory(),
            new CdbXmlContactInfoImporter(),
            new CdbXMLToJsonLDLabelImporter($this->createMock(ReadRepositoryInterface::class))
        );

        $this->projector = new EventLDProjector(
            $this->documentRepository,
            $iriGenerator,
            new CallableIriGenerator(fn ($id) => 'https://io.uitdatabank.dev/places/' . $id),
            new CallableIriGenerator(fn ($id) => 'https://io.uitdatabank.dev/organizers/' . $id),
            $this->placeService,
            $this->organizerRepository,
            $this->serializer,
            $this->iriOfferIdentifierFactory,
            $this->cdbXMLImporter,
            new JsonDocumentLanguageEnricher(
                new EventJsonDocumentLanguageAnalyzer()
            ),
            new EventTypeResolver(),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ],
            new VideoNormalizer(
                [
                    'nl' => 'Copyright afgehandeld door %s',
                    'fr' => 'Droits d\'auteur gérés par %s',
                    'de' => 'Urheberrecht gehandhabt von %s',
                    'en' => 'Copyright handled by %s',
                ]
            ),
            new CompletenessFromWeights(
                Weights::fromConfig([
                    'type' => 12,
                    'theme' => 5,
                    'calendarType' => 12,
                    'location' => 12,
                    'name' => 12,
                    'typicalAgeRange' => 12,
                    'mediaObject' => 8,
                    'description' => 9,
                    'priceInfo' => 7,
                    'contactPoint' => 3,
                    'bookingInfo' => 3,
                    'organizer' => 3,
                    'videos' => 2,
                ])
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_new_events_without_theme(): void
    {
        $eventId = '1';

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00')
        );

        $eventCreated = $this->createEventCreated($eventId, $calendar, null);

        $jsonLD = $this->createJsonLD($eventId, new Language('en'));
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->typicalAgeRange = '-';
        $jsonLD->playhead = 1;
        $jsonLD->completeness = 60;

        $this->mockPlaceService();

        $body = $this->project(
            $eventCreated,
            $eventId,
            new Metadata(),
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        $this->assertEquals($jsonLD, $body);
    }

    /**
     * @test
     */
    public function it_handles_copied_events_with_an_incorrect_place_type(): void
    {
        $eventId = 'f8e4f084-1b75-4893-b2b9-fc67fd6e73fb';
        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            'some representative title',
            new EventType('0.14.0.0.0', 'Monument'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(
                CalendarType::PERIODIC(),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00')
            ),
            null
        );

        $this->project($eventCreated, $eventId);

        $this->project(
            new Published($eventId, new \DateTime()),
            $eventId
        );

        $newEventId = 'f0b24f97-4b03-4eb2-96d1-5074819a7648';
        $eventCopied = new EventCopied(
            $newEventId,
            $eventId,
            new Calendar(
                CalendarType::PERIODIC(),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2022-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2022-01-26T13:25:21+01:00')
            )
        );

        $recordedOn = '2022-01-20T13:25:21+01:00';

        $jsonLD = json_decode(file_get_contents(__DIR__ . '/copied_event_with_place_type.json'));
        $jsonLD->created = $recordedOn;
        $jsonLD->modified = $recordedOn;
        $jsonLD->playhead = 1;
        $jsonLD->completeness = 60;

        $body = $this->project(
            $eventCopied,
            $newEventId,
            new Metadata(),
            DateTime::fromString($recordedOn)
        );

        $this->assertEquals($jsonLD, $body);
    }

    /**
     * @test
     */
    public function it_handles_new_events_with_theme(): void
    {
        $eventId = '1';
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00')
        );
        $theme = new Theme('123', 'theme label');

        $eventCreated = $this->createEventCreated($eventId, $calendar, $theme);

        $jsonLD = $this->createJsonLD($eventId, new Language('en'));
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $jsonLD->typicalAgeRange = '-';
        $jsonLD->playhead = 1;
        $jsonLD->completeness = 65;

        $this->mockPlaceService();

        $body = $this->project(
            $eventCreated,
            $eventId,
            null,
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        $this->assertEquals(
            $jsonLD,
            $body
        );
    }

    /**
     * @test
     * @dataProvider eventCreatorDataProvider
     */
    public function it_handles_new_events_with_creator(Metadata $metadata, string $expectedCreator): void
    {
        $eventId = '1';
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00')
        );
        $theme = new Theme('123', 'theme label');

        $eventCreated = $this->createEventCreated($eventId, $calendar, $theme);

        $jsonLD = $this->createJsonLD($eventId, new Language('en'));
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $jsonLD->typicalAgeRange = '-';
        $jsonLD->creator = $expectedCreator;
        $jsonLD->playhead = 1;
        $jsonLD->completeness = 65;

        $this->mockPlaceService();

        $body = $this->project(
            $eventCreated,
            $eventId,
            $metadata,
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        $this->assertEquals($jsonLD, $body);
    }

    public function eventCreatorDataProvider(): array
    {
        return [
            [
                new Metadata(
                    [
                        'user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c',
                    ]
                ),
                '20a72430-7e3e-4b75-ab59-043156b3169c',
            ],
            [
                new Metadata(
                    [
                        'user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c',
                    ]
                ),
                '20a72430-7e3e-4b75-ab59-043156b3169c',
            ],
            [
                new Metadata(
                    [
                        'user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c',
                    ]
                ),
                '20a72430-7e3e-4b75-ab59-043156b3169c',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_changes_the_creator_if_the_owner_changes(): void
    {
        $eventId = '5c83ab42-1a6d-497d-8580-c85681250a94';
        $originalOwner = 'f7a4c1d9-dd05-40e8-98fe-637265ce8530';
        $newOwner = '55153b44-c43b-4bcc-80cd-e9beb9f3557d';

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode(['creator' => $originalOwner])
        );
        $this->documentRepository->save($initialDocument);

        $ownerChanged = new OwnerChanged($eventId, $newOwner);

        $updatedJsonLd = $this->project(
            $ownerChanged,
            $eventId
        );

        $this->assertEquals($updatedJsonLd->creator, $newOwner);
    }

    /**
     * @test
     */
    public function it_handles_copy_event(): void
    {
        $originalEventId = 'f8e4f084-1b75-4893-b2b9-fc67fd6e73fb';
        $originalCalendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2017-01-26T13:25:21+01:00')
        );
        $eventCreated = $this->createEventCreated($originalEventId, $originalCalendar, null);

        $this->project($eventCreated, $originalEventId);

        $this->project(
            new Published($originalEventId, new \DateTime()),
            $originalEventId
        );

        $this->project(
            new LabelAdded($originalEventId, '2dotstwice', true),
            $originalEventId
        );

        $this->project(
            new LabelAdded($originalEventId, 'cultuurnet', false),
            $originalEventId
        );

        $eventId = 'f0b24f97-4b03-4eb2-96d1-5074819a7648';
        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-27T13:25:21+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-28T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-29T13:25:21+01:00')
            ),
        ];
        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-29T13:25:21+01:00'),
            $timestamps
        );
        $eventCopied = new EventCopied($eventId, $originalEventId, $calendar);

        $recordedOn = '2018-01-01T11:55:55+01:00';
        $body = $this->project(
            $eventCopied,
            $eventId,
            new Metadata(['user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c']),
            DateTime::fromString($recordedOn)
        );

        $expectedJsonLD = json_decode(file_get_contents(__DIR__ . '/copied_event.json'));
        $expectedJsonLD->created = $recordedOn;
        $expectedJsonLD->modified = $recordedOn;
        $expectedJsonLD->creator = '20a72430-7e3e-4b75-ab59-043156b3169c';
        $expectedJsonLD->completeness = 60;

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_copied_event_with_work_hours_removed(): void
    {
        $eventCreated = $this->createEventCreated('f8e4f084-1b75-4893-b2b9-fc67fd6e73fb', $this->aPeriodicCalendarWithWorkScheme(), null);

        $this->project($eventCreated, $eventCreated->getEventId());
        $this->project($this->aPublishedEvent($eventCreated), $eventCreated->getEventId());

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-29T13:25:21+01:00')
        );

        $eventCopied = new EventCopied('f0b24f97-4b03-4eb2-96d1-5074819a7648', $eventCreated->getEventId(), $calendar);

        $recordedOn = '2018-01-01T11:55:55+01:00';
        $userId = '20a72430-7e3e-4b75-ab59-043156b3169c';

        $body = $this->project(
            $eventCopied,
            $eventCopied->getItemId(),
            new Metadata(['user_id' => '' . $userId . '']),
            DateTime::fromString($recordedOn)
        );

        $expectedJsonLD = json_decode(file_get_contents(__DIR__ . '/copied_event_without_working_hours.json'));
        $expectedJsonLD->created = $recordedOn;
        $expectedJsonLD->modified = $recordedOn;
        $expectedJsonLD->creator = $userId;
        $expectedJsonLD->completeness = 60;

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_start_date_as_available_to_for_workshops(): void
    {
        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-01T12:00:00+01:00');
        $eventType = new EventType('0.3.1.0.0', 'Cursus of workshop');

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            $startDate,
            $endDate,
            [
                new Timestamp($startDate, $endDate),
            ]
        );

        $eventCreated = new EventCreated(
            '1',
            new Language('en'),
            'Workshop with single day',
            $eventType,
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            $calendar
        );

        $body = $this->project($eventCreated, $eventCreated->getEventId());
        $this->assertEquals($startDate->format(DATE_ATOM), $body->availableTo);

        $this->project($this->aPublishedEvent($eventCreated), $eventCreated->getEventId());
        $eventCopied = new EventCopied('2', $eventCreated->getEventId(), $calendar);

        $body = $this->project($eventCopied, '2');
        $this->assertEquals($startDate->format(DATE_ATOM), $body->availableTo);
    }

    /**
     * @test
     */
    public function it_projects_end_date_as_available_to_for_other_event_types(): void
    {
        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-01T12:00:00+01:00');
        $eventType = new EventType('1.50.0.0.0', 'Eten en drinken');

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            $startDate,
            $endDate,
            [
                new Timestamp($startDate, $endDate),
            ]
        );

        $eventCreated = new EventCreated(
            '1',
            new Language('en'),
            'Workshop with single day',
            $eventType,
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            $calendar
        );

        $body = $this->project($eventCreated, $eventCreated->getEventId());
        $this->assertEquals($endDate->format(DATE_ATOM), $body->availableTo);

        $this->project($this->aPublishedEvent($eventCreated), $eventCreated->getEventId());
        $eventCopied = new EventCopied('2', $eventCreated->getEventId(), $calendar);

        $body = $this->project($eventCopied, '2');
        $this->assertEquals($endDate->format(DATE_ATOM), $body->availableTo);
    }

    /**
     * @test
     */
    public function it_handles_new_events_with_multiple_timestamps(): void
    {
        $eventId = '926fca95-010e-46b1-8b8e-abe757dd32d5';

        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-27T13:25:21+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-28T13:25:21+01:00'),
                \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-29T13:25:21+01:00')
            ),
        ];

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-29T13:25:21+01:00'),
            $timestamps
        );

        $theme = new Theme('123', 'theme label');

        $eventCreated = $this->createEventCreated($eventId, $calendar, $theme);

        $jsonLD = $this->createJsonLD($eventId, new Language('en'));
        $jsonLD->calendarType = 'multiple';
        $jsonLD->startDate = '2015-01-26T13:25:21+01:00';
        $jsonLD->endDate = '2015-01-29T13:25:21+01:00';
        $jsonLD->subEvent = [
            (object)[
                'id' => 0,
                '@type' => 'Event',
                'startDate' => '2015-01-26T13:25:21+01:00',
                'endDate' => '2015-01-27T13:25:21+01:00',
                'status' => (object) ['type' => 'Available'],
                'bookingAvailability' => (object) ['type' => 'Available'],
            ],
            (object)[
                'id' => 1,
                '@type' => 'Event',
                'startDate' => '2015-01-28T13:25:21+01:00',
                'endDate' => '2015-01-29T13:25:21+01:00',
                'status' => (object) ['type' => 'Available'],
                'bookingAvailability' => (object) ['type' => 'Available'],
            ],
        ];
        $jsonLD->availableTo = $jsonLD->endDate;
        $jsonLD->sameAs = [
            'http://www.uitinvlaanderen.be/agenda/e/some-representative-title/' . $eventId,
        ];
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $jsonLD->typicalAgeRange = '-';
        $jsonLD->playhead = 1;
        $jsonLD->completeness = 65;

        $this->mockPlaceService();

        $body = $this->project(
            $eventCreated,
            $eventId,
            null,
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        $this->assertEquals($jsonLD, $body);
    }

    /**
     * @test
     */
    public function it_should_set_a_main_language_when_importing_cdbxml(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_calendar_periods.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertEquals('nl', $body->mainLanguage);
    }

    /**
     * @test
     */
    public function it_should_not_change_main_language_when_updating(): void
    {
        // First make sure there is already an event, so it is a real update.
        $eventId = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2017-01-26T13:25:21+01:00')
        );
        $eventCreated = $this->createEventCreated($eventId, $calendar, null);
        $this->mockPlaceService();
        $this->project(
            $eventCreated,
            $eventId,
            new Metadata(),
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        // Now do the real update.
        $event = $this->cdbXMLEventFactory->eventUpdatedFromUDB2(
            'samples/event_with_calendar_periods.cdbxml.xml'
        );

        $body = $this->project($event, $eventId);

        $this->assertEquals((new Language('en'))->toString(), $body->mainLanguage);
    }

    /**
     * @test
     */
    public function it_strips_empty_keywords_when_importing_from_udb2(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $expectedLabels = ['gent', 'Quiz', 'Gent on Files'];

        $this->assertEquals(
            $expectedLabels,
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_does_remove_existing_location_when_updating_from_udb2_without_location_id(): void
    {
        $event = $this->cdbXMLEventFactory->eventUpdatedFromUDB2(
            'samples/event_with_udb3_place.cdbxml.xml'
        );

        // add the event json to memory
        $this->documentRepository->save(new JsonDocument(
            CdbXMLEventFactory::AN_EVENT_ID,
            file_get_contents(
                __DIR__ . '/../../samples/event_with_udb3_place.json'
            )
        ));

        $body = $this->project($event, $event->getEventId());
        // asset the location is still a place object
        $this->assertEquals('Place', $body->location->{'@type'});
        $this->assertArrayNotHasKey(
            '@id',
            (array) $body->location
        );
        $this->assertArrayHasKey(
            'name',
            (array) $body->location
        );
        $this->assertArrayHasKey(
            'address',
            (array) $body->location
        );
    }

    /**
     * @test
     */
    public function it_can_update_an_event_from_udb2_even_if_it_has_been_deleted(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );
        $eventId = $event->getEventId();

        $this->project($event, $event->getEventId());

        $eventDeleted = new EventDeleted($eventId);

        $this->project($eventDeleted, $eventDeleted->getItemId());

        $eventUpdatedFromUdb2 = $this->cdbXMLEventFactory->eventUpdatedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );
        $this->project($eventUpdatedFromUdb2, $eventUpdatedFromUdb2->getEventId());

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_labels_property(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_without_keywords.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertFalse(property_exists($body, 'labels'));
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_image_property(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_without_image.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertObjectNotHasProperty('image', $body);
    }

    /**
     * @test
     */
    public function it_adds_a_bookingInfo_property_when_cdbxml_has_pricevalue(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_price_value_and_description.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new \stdClass();
        $expectedBookingInfo->priceCurrency = 'EUR';
        $expectedBookingInfo->price = 9.99;
        $expectedBookingInfo->description = 'Iedereen aan dezelfde prijs';

        $this->assertIsObject($bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_adds_the_pricedescription_from_cdbxml_to_bookingInfo(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_price_value_and_description.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new \stdClass();
        $expectedBookingInfo->priceCurrency = 'EUR';
        $expectedBookingInfo->price = 9.99;
        $expectedBookingInfo->description = 'Iedereen aan dezelfde prijs';

        $this->assertIsObject($bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_does_not_add_a_missing_price_from_cdbxml_to_bookingInfo(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_only_price_description.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new stdClass();
        $expectedBookingInfo->description = 'Gratis voor iedereen!';

        $this->assertIsObject($bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_does_not_add_booking_info_when_price_and_reservation_contact_channels_are_missing(): void
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_without_price.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertObjectNotHasProperty('bookingInfo', $body);
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label(): void
    {
        $labelAdded = new LabelAdded(
            'foo',
            'label B'
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($labelAdded, 'foo');

        $this->assertEquals(
            ['label A', 'label B'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_removal_of_a_label(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A', 'label B', 'label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            'label B'
        );

        $body = $this->project($labelRemoved, 'foo');

        $this->assertEquals(
            ['label A', 'label C'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label_to_an_event_without_existing_labels(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'bar' => 'stool',
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelAdded = new LabelAdded(
            'foo',
            'label B'
        );

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = new stdClass();
        $expectedBody->bar = 'stool';
        $expectedBody->labels = ['label B'];
        $expectedBody->modified = $this->recordedOn->toString();
        $expectedBody->playhead = 1;
        $expectedBody->completeness = 0;

        $this->assertEquals(
            $expectedBody,
            $body
        );
    }

    public static function majorInfoUpdatedDataProvider(): array
    {
        return [
            'Update with offline location' => [
                null,
                new MajorInfoUpdated(
                    'foo',
                    'new title',
                    new EventType('0.50.4.0.1', 'concertnew'),
                    new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
                    new Calendar(
                        CalendarType::PERIODIC(),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-02-26T13:25:21+01:00')
                    ),
                    new Theme('123', 'theme label')
                ),
                AttendanceMode::offline(),
                [
                    '@type' => 'Place',
                    '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
                ],
            ],
            'Update with online location' => [
                null,
                new MajorInfoUpdated(
                    'foo',
                    'new title',
                    new EventType('0.50.4.0.1', 'concertnew'),
                    new LocationId('00000000-0000-0000-0000-000000000000'),
                    new Calendar(
                        CalendarType::PERIODIC(),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-02-26T13:25:21+01:00')
                    ),
                    new Theme('123', 'theme label')
                ),
                AttendanceMode::online(),
                EventLDProjectorTest::getNilLocationJsonLD(),
            ],
            'Update with offline location on event with online url' => [
                'https://www.online.be',
                new MajorInfoUpdated(
                    'foo',
                    'new title',
                    new EventType('0.50.4.0.1', 'concertnew'),
                    new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
                    new Calendar(
                        CalendarType::PERIODIC(),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
                        \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-02-26T13:25:21+01:00')
                    ),
                    new Theme('123', 'theme label')
                ),
                AttendanceMode::mixed(),
                [
                    '@type' => 'Place',
                    '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider majorInfoUpdatedDataProvider
     */
    public function it_projects_the_updating_of_major_info(
        ?string $givenOnlineUrl,
        MajorInfoUpdated $givenMajorInfoUpdated,
        AttendanceMode $givenAttendanceMode,
        array $expectedLocation
    ): void {
        if (!$givenMajorInfoUpdated->getLocation()->isNilLocation()) {
            $this->mockPlaceService();
        }

        $id = $givenMajorInfoUpdated->getItemId();

        $jsonLD = new stdClass();
        $jsonLD->id = $id;
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = ['en' => 'some representative title'];
        $jsonLD->location = [
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
        if ($givenOnlineUrl) {
            $jsonLD->onlineUrl = $givenOnlineUrl;
        }
        $jsonLD->calendarType = 'permanent';
        $jsonLD->terms = [
            [
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];

        $initialDocument = (new JsonDocument('foo'))
            ->withBody($jsonLD);

        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = new stdClass();
        $expectedJsonLD->id = $id;
        $expectedJsonLD->mainLanguage = 'en';
        $expectedJsonLD->name = (object)[
            'en' => 'new title',
        ];
        $expectedJsonLD->location = (object) $expectedLocation;
        if ($givenOnlineUrl) {
            $expectedJsonLD->onlineUrl = $givenOnlineUrl;
        }
        $expectedJsonLD->attendanceMode = $givenAttendanceMode->toString();
        $expectedJsonLD->calendarType = 'periodic';
        $expectedJsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.1',
                'label' => 'concertnew',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $expectedJsonLD->languages = ['en'];
        $expectedJsonLD->completedLanguages = ['en'];
        $expectedJsonLD->startDate = '2015-01-26T13:25:21+01:00';
        $expectedJsonLD->endDate = '2015-02-26T13:25:21+01:00';
        $expectedJsonLD->availableTo = $expectedJsonLD->endDate;
        $expectedJsonLD->modified = $this->recordedOn->toString();
        $expectedJsonLD->status = (object)[
            'type' => 'Available',
        ];
        $expectedJsonLD->bookingAvailability = (object)[
            'type' => 'Available',
        ];
        $expectedJsonLD->playhead = 1;
        $expectedJsonLD->completeness = 53;

        $body = $this->project($givenMajorInfoUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_calendar_updated(): void
    {
        $eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $calendarUpdated = new CalendarUpdated($eventId, $calendar);

        $jsonLD = new stdClass();
        $jsonLD->id = $eventId;

        $initialDocument = (new JsonDocument('foo'))
            ->withBody($jsonLD);
        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
        ];
        $expectedJsonLD->calendarType = 'periodic';
        $expectedJsonLD->startDate = '2020-01-26T11:11:11+01:00';
        $expectedJsonLD->endDate = '2020-01-27T12:12:12+01:00';
        $expectedJsonLD->availableTo = '2020-01-27T12:12:12+01:00';
        $expectedJsonLD->modified = $this->recordedOn->toString();
        $expectedJsonLD->status = (object) [
            'type' => 'Available',
        ];
        $expectedJsonLD->bookingAvailability = (object) [
            'type' => 'Available',
        ];
        $expectedJsonLD->playhead = 1;
        $expectedJsonLD->completeness = 12;

        $body = $this->project($calendarUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_location(): void
    {
        $this->mockPlaceService();

        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';
        $locationId = new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e');

        $locationUpdated = new LocationUpdated(
            $eventId,
            $locationId
        );

        $jsonLD = new stdClass();
        $jsonLD->id = $eventId;
        $jsonLD->location = [
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];

        $initialDocument = (new JsonDocument($eventId))
            ->withBody($jsonLD);
        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = new stdClass();
        $expectedJsonLD->id = $eventId;
        $expectedJsonLD->location = (object)[
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
        $expectedJsonLD->modified = $this->recordedOn->toString();
        $expectedJsonLD->playhead = 1;
        $expectedJsonLD->completeness = 12;

        $body = $this->project($locationUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_geo_coordinates(): void
    {
        $id = 'ea328f14-a3c8-4f71-abd9-00cd0a2cf217';

        $initialDocument = new JsonDocument(
            $id,
            Json::encode(
                [
                    '@id' => 'http://uitdatabank/event/' . $id,
                    '@type' => 'Event',
                    'name' => [
                        'nl' => 'Test',
                    ],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                    'location' => (object) [
                        '@type' => 'Place',
                        '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
                    ],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $coordinatesUpdated = new GeoCoordinatesUpdated(
            $id,
            new Coordinates(
                new Latitude(1.1234567),
                new Longitude(-0.34567)
            )
        );

        $expectedBody = (object) [
            '@id' => 'http://uitdatabank/event/' . $id,
            '@type' => 'Event',
            'name' => (object) ['nl' => 'Test'],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'location' => (object) [
                '@type' => 'Place',
                '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
                'geo' => (object) [
                    'latitude' => 1.1234567,
                    'longitude' => -0.34567,
                ],
            ],
            'modified' => $this->recordedOn->toString(),
            'completeness' => 24,
        ];
        $expectedBody->playhead = 1;

        $body = $this->project($coordinatesUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @group issue-III-1730
     * @test
     */
    public function it_keeps_alien_terms_imported_from_udb2_when_updating_major_info(): void
    {
        $importedFromUDB2 = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );

        $title = 'new title';
        $eventType = new EventType('0.50.4.0.1', 'concertnew');
        $location = new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e');
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(DateTimeInterface::ATOM, '2015-02-26T13:25:21+01:00')
        );
        $theme = new Theme('123', 'theme label');
        $majorInfoUpdated = new MajorInfoUpdated(
            $importedFromUDB2->getEventId(),
            $title,
            $eventType,
            $location,
            $calendar,
            $theme
        );

        $events = [$importedFromUDB2, $majorInfoUpdated];
        $body = null;
        foreach ($events as $event) {
            $body = $this->project($event, $importedFromUDB2->getEventId());
        }

        $expectedTerms = [
            (object)[
               'id' => 'reg.359',
               'label' => 'Kunststad Gent',
               'domain' => 'flanderstouristregion',
            ],
            (object)[
                'id' => 'reg.1258',
                'label' => '9000 Gent',
                'domain' => 'flandersregion',
            ],
            (object)[
                'id' => '0.50.4.0.1',
                'label' => 'concertnew',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];

        $this->assertEquals($expectedTerms, $body->terms);
    }

    /**
     * @test
     */
    public function it_handles_event_created_with_nil_location(): void
    {
        $eventCreated = new EventCreated(
            '1',
            new Language('en'),
            'Online workshop',
            new EventType('0.3.1.0.0', 'Cursus of workshop'),
            new LocationId(LocationId::NIL_LOCATION),
            new Calendar(CalendarType::PERMANENT())
        );

        $body = $this->project($eventCreated, $eventCreated->getEventId());

        $this->assertEquals(
            (object) EventLDProjectorTest::getNilLocationJsonLD(),
            $body->location
        );
    }

    /**
     * @test
     */
    public function it_projects_attendanceMode_updated(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $attendanceModeUpdated = new AttendanceModeUpdated($eventId, AttendanceMode::online()->toString());

        $body = $this->project($attendanceModeUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'attendanceMode' => AttendanceMode::online()->toString(),
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_projects_onlineUrl_updated(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $onlineUrlUpdated = new OnlineUrlUpdated($eventId, 'https://www.publiq.be/livestream');

        $body = $this->project($onlineUrlUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_projects_onlineUrl_deleted(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->project(
            new OnlineUrlUpdated($eventId, 'https://www.publiq.be/livestream'),
            $eventId,
            null,
            $this->recordedOn->toBroadwayDateTime()
        );
        $body = $this->project(
            new OnlineUrlDeleted($eventId),
            $eventId,
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_projects_updating_audience(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $audienceUpdated = new AudienceUpdated(
            $eventId,
            new Audience(AudienceType::education())
        );

        $body = $this->project($audienceUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'audience' => (object) ['audienceType' => 'education'],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_updates_workflow_status_on_delete(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $eventDeleted = new EventDeleted($eventId);

        $body = $this->project($eventDeleted, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'workflowStatus' => 'DELETED',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_should_project_the_new_theme_as_a_term_when_updated(): void
    {
        $itemId = '528e26f7-9bad-48b8-b47f-c3a4b5b92bf6';
        $theme = new Theme('1.8.3.3.0', 'Dance');
        $themeUpdatedEvent = new ThemeUpdated($itemId, $theme);

        $expectedTerms = [
            (object) [
                'id' => '1.8.3.3.0',
                'label' => 'Dance',
                'domain' => 'theme',
            ],
        ];

        $updatedItem = $this->project($themeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }


    /**
     * @test
     */
    public function it_should_replace_the_existing_theme_term_when_updating_with_a_new_theme(): void
    {
        $itemId = '1a08516e-aba4-47f0-887e-df37b61a1e8d';
        $documentWithExistingTerms = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'terms' => [
                    (object) [
                        'id' => '1.8.3.3.0',
                        'label' => 'Dance',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                        'label' => 'Archeologische Site',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $theme = new Theme('1.8.2.0.0', 'Jazz en booze');
        $themeUpdatedEvent = new ThemeUpdated($itemId, $theme);

        $this->documentRepository->save($documentWithExistingTerms);

        $expectedTerms = [
            (object) [
                'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                'label' => 'Archeologische Site',
                'domain' => 'eventtype',
            ],
            (object) [
                'id' => '1.8.2.0.0',
                'label' => 'Jazz en booze',
                'domain' => 'theme',
            ],
        ];

        $updatedItem = $this->project($themeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }

    /**
     * @test
     * @dataProvider typesThatAreAvailableTillStart
     */
    public function it_keeps_start_date_for_selected_types_on_calendar_updated(string $termId, string $termName): void
    {
        $eventId = '1a08516e-aba4-47f0-887e-df37b61a1e8d';

        $eventThatsAvailableTillStart = new JsonDocument(
            $eventId,
            Json::encode([
                '@id' => $eventId,
                '@type' => 'event',
                'terms' => [
                    (object) [
                        'id' => '1.51.12.0.0',
                        'label' => 'Omnisport en andere',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => $termId,
                        'label' => $termName,
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($eventThatsAvailableTillStart);

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-01T12:00:00+01:00');
        $calendarUpdated = new CalendarUpdated(
            $eventId,
            new Calendar(
                CalendarType::SINGLE(),
                $startDate,
                $endDate,
                [
                    new Timestamp($startDate, $endDate),
                ]
            )
        );

        $updatedItem = $this->project($calendarUpdated, $eventId);

        $this->assertEquals($startDate->format(DATE_ATOM), $updatedItem->availableTo);
    }

    /**
     * @test
     * @dataProvider typesThatAreAvailableTillStart
     */
    public function it_sets_available_to_start_date_for_selected_types_on_type_updated(string $termId): void
    {
        $eventId = '1a08516e-aba4-47f0-887e-df37b61a1e8d';

        $eventThatShouldAvailableTillStart = new JsonDocument(
            $eventId,
            Json::encode([
                '@id' => $eventId,
                '@type' => 'event',
                'calendar' => [
                    'calendarType' => 'single',
                    'timeSpans' => [
                        [
                            'start' => '2018-01-01T12:00:00+01:00',
                            'end' => '2020-01-01T12:00:00+01:00',
                        ],
                    ],
                ],
                'startDate' => '2018-01-01T12:00:00+01:00',
                'endDate' => '2020-01-01T12:00:00+01:00',
                'availableTo' => '2020-01-01T12:00:00+01:00',
                'terms' => [
                    (object) [
                        'id' => '1.51.12.0.0',
                        'label' => 'Omnisport en andere',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '0.7.0.0.0',
                        'label' => 'Begeleide uitstap of rondleiding',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($eventThatShouldAvailableTillStart);

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');

        $typeUpdated = new TypeUpdated($eventId, (new EventTypeResolver())->byId($termId));

        $updatedItem = $this->project($typeUpdated, $eventId);

        $this->assertEquals($startDate->format(DATE_ATOM), $updatedItem->availableTo);
    }

    /**
     * @test
     * @dataProvider typesThatAreAvailableTillStart
     */
    public function it_sets_available_to_end_date_on_type_updated_from_selected_types(string $termId, string $termName): void
    {
        $eventId = '1a08516e-aba4-47f0-887e-df37b61a1e8d';

        $eventThatShouldAvailableTillStart = new JsonDocument(
            $eventId,
            Json::encode([
                '@id' => $eventId,
                '@type' => 'event',
                'calendar' => [
                    'calendarType' => 'single',
                    'timeSpans' => [
                        [
                            'start' => '2018-01-01T12:00:00+01:00',
                            'end' => '2020-01-01T12:00:00+01:00',
                        ],
                    ],
                ],
                'startDate' => '2018-01-01T12:00:00+01:00',
                'endDate' => '2020-01-01T12:00:00+01:00',
                'availableTo' => '2018-01-01T12:00:00+01:00',
                'terms' => [
                    (object) [
                        'id' => $termId,
                        'label' => $termName,
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '0.7.0.0.0',
                        'label' => 'Begeleide uitstap of rondleiding',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($eventThatShouldAvailableTillStart);

        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-01T12:00:00+01:00');

        $typeUpdated = new TypeUpdated($eventId, (new EventTypeResolver())->byId('0.50.4.0.0'));

        $updatedItem = $this->project($typeUpdated, $eventId);

        $this->assertEquals($endDate->format(DATE_ATOM), $updatedItem->availableTo);
    }

    /**
     * @test
     */
    public function it_keeps_available_to_for_standard_types_on_type_updated(): void
    {
        $eventId = '1a08516e-aba4-47f0-887e-df37b61a1e8d';

        $eventThatShouldAvailableTillStart = new JsonDocument(
            $eventId,
            Json::encode([
                '@id' => $eventId,
                '@type' => 'event',
                'calendar' => [
                    'calendarType' => 'single',
                    'timeSpans' => [
                        [
                            'start' => '2018-01-01T12:00:00+01:00',
                            'end' => '2020-01-01T12:00:00+01:00',
                        ],
                    ],
                ],
                'startDate' => '2018-01-01T12:00:00+01:00',
                'endDate' => '2020-01-01T12:00:00+01:00',
                'availableTo' => '2020-01-01T12:00:00+01:00',
                'terms' => [
                    (object) [
                        'id' => '1.51.12.0.0',
                        'label' => 'Omnisport en andere',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '0.7.0.0.0',
                        'label' => 'Begeleide uitstap of rondleiding',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($eventThatShouldAvailableTillStart);

        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-01T12:00:00+01:00');

        $typeUpdated = new TypeUpdated($eventId, (new EventTypeResolver())->byId('0.50.4.0.0'));

        $updatedItem = $this->project($typeUpdated, $eventId);

        $this->assertEquals($endDate->format(DATE_ATOM), $updatedItem->availableTo);
    }

    /**
     * @test
     */
    public function it_handles_theme_removed(): void
    {
        $itemId = 'd3140997-5e22-4e57-b6d3-04fc8d9b86cb';

        $documentWithExistingTerms = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'terms' => [
                    (object) [
                        'id' => '1.8.3.3.0',
                        'label' => 'Dance',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                        'label' => 'Archeologische Site',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($documentWithExistingTerms);

        $updatedItem = $this->project(new ThemeRemoved($itemId), $itemId);
        $this->assertEquals(
            [
                (object) [
                    'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                    'label' => 'Archeologische Site',
                    'domain' => 'eventtype',
                ],
            ],
            $updatedItem->terms
        );
    }

    /**
     * @test
     * @dataProvider eventUpdateDataProvider
     */
    public function it_prioritizes_udb3_media_when_updating_an_event(
        JsonDocument $documentWithUDB3Media,
        DomainMessage $domainMessage,
        array $expectedMediaObjects
    ): void {
        $this->documentRepository->save($documentWithUDB3Media);

        $this->projector->handle($domainMessage);

        $this->assertEquals(
            $expectedMediaObjects,
            $this->documentRepository->fetch(CdbXMLEventFactory::AN_EVENT_ID)->getBody()->mediaObject
        );
    }

    public function typesThatAreAvailableTillStart(): array
    {
        return [
            ['0.3.1.0.0', 'Lessenreeks'],
            ['0.57.0.0.0', 'Kamp of vakantie'],
        ];
    }

    public function eventUpdateDataProvider(): array
    {
        $documentWithUDB3Media = new JsonDocument(
            CdbXMLEventFactory::AN_EVENT_ID,
            Json::encode([
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                    ],
                ],
            ])
        );

        $expectedMediaObjects = [
            (object) [
                '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'description' => 'The Gleaners',
                'copyrightHolder' => 'Jean-François Millet',
            ],
        ];

        $importedDate = '2015-03-01T10:17:19.176169+02:00';

        $metadata = [];
        $metadata['consumer']['name'] = 'UiTDatabank';

        $eventUpdatedFromUDB2 = new EventUpdatedFromUDB2(
            'foo',
            file_get_contents(__DIR__ . '/../../samples/event_with_photo.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return [
            'udb2' => [
                $documentWithUDB3Media,
                new DomainMessage(
                    'dcd1ef37-0608-4824-afe3-99124feda64b',
                    1,
                    new Metadata($metadata),
                    $eventUpdatedFromUDB2,
                    DateTime::fromString($importedDate)
                ),
                $expectedMediaObjects,
            ],
        ];
    }

    private function createEventCreated(
        string $eventId,
        Calendar $calendar = null,
        Theme $theme = null
    ): EventCreated {
        $calendar = $calendar ?? new Calendar(CalendarType::PERMANENT());

        return new EventCreated(
            $eventId,
            new Language('en'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            $calendar,
            $theme
        );
    }

    private function createJsonLD(string $eventId, Language $mainLanguage): stdClass
    {
        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $eventId;
        $jsonLD->{'@context'} = '/contexts/event';
        $jsonLD->mainLanguage = $mainLanguage->getCode();
        $jsonLD->name = (object)[
            $mainLanguage->getCode() => 'some representative title',
        ];
        $jsonLD->location = (object)[
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
        $jsonLD->calendarType = 'periodic';
        $jsonLD->startDate = '2015-01-26T13:25:21+01:00';
        $jsonLD->endDate = '2015-01-26T13:25:21+01:00';
        $jsonLD->availableTo = $jsonLD->startDate;
        $jsonLD->sameAs = [
            'http://www.uitinvlaanderen.be/agenda/e/some-representative-title/1',
        ];
        $jsonLD->created = '2015-01-20T13:25:21+01:00';
        $jsonLD->modified = '2015-01-20T13:25:21+01:00';
        $jsonLD->workflowStatus = 'DRAFT';
        $jsonLD->attendanceMode = AttendanceMode::offline()->toString();
        $jsonLD->audience = (object)['audienceType' => 'everyone'];
        $jsonLD->languages = [$mainLanguage->getCode()];
        $jsonLD->completedLanguages = [$mainLanguage->getCode()];
        $jsonLD->status = (object)[
            'type' => 'Available',
        ];
        $jsonLD->bookingAvailability = (object)[
            'type' => 'Available',
        ];

        return $jsonLD;
    }

    private function mockPlaceService(): void
    {
        // Set up the placeService so that it does not know about the JSON-LD
        // representation of the Place yet and only returns the URI of the
        // Place.
        $this->placeService->expects($this->once())
            ->method('getEntity')
            ->with('395fe7eb-9bac-4647-acae-316b6446a85e')
            ->willThrowException(new EntityNotFoundException());
        $this->placeService->expects($this->once())
            ->method('iri')
            ->willReturnCallback(
                function ($argument) {
                    return 'http://example.com/entity/' . $argument;
                }
            );
    }


    protected function aPeriodicCalendarWithWorkScheme(): Calendar
    {
        return new Calendar(
            CalendarType::PERIODIC(),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00'),
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(8), new Minute(0)),
                    new OpeningTime(new Hour(12), new Minute(59)),
                    new DayOfWeekCollection(
                        DayOfWeek::MONDAY(),
                        DayOfWeek::TUESDAY()
                    )
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(10), new Minute(0)),
                    new OpeningTime(new Hour(14), new Minute(0)),
                    new DayOfWeekCollection(
                        DayOfWeek::SATURDAY()
                    )
                ),
            ]
        );
    }

    protected function aPublishedEvent(EventCreated $eventCreated): Published
    {
        return new Published($eventCreated->getEventId(), new \DateTime());
    }

    private static function getNilLocationJsonLD(): array
    {
        return [
            '@type' => 'Place',
            '@id' => 'https://io.uitdatabank.dev/places/00000000-0000-0000-0000-000000000000',
            'mainLanguage' => 'nl',
            'name' => (object) [
                'nl' => 'Online',
            ],
            'terms' => [
                (object) [
                    'id' => '0.8.0.0.0',
                    'label' => 'Openbare ruimte',
                    'domain' => 'eventtype',
                ],
            ],
            'calendarType' => 'permanent',
            'status' => (object) [
                'type' => 'Available',
            ],
            'bookingAvailability' => (object) [
                'type' => 'Available',
            ],
            'address' => (object) [
                'nl' =>(object) [
                    'addressCountry' => 'BE',
                    'addressLocality' => '___',
                    'postalCode' => '0000',
                    'streetAddress' => '___',
                ],
            ],
        ];
    }
}
