<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\CalendarType;
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
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\OfferLDProjectorTestBase;
use CultuurNet\UDB3\PlaceService;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\Serializer\Serializer;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class EventLDProjectorTest extends OfferLDProjectorTestBase
{
    const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @var PlaceService|MockObject
     */
    private $placeService;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var CdbXMLEventFactory
     */
    private $cdbXMLEventFactory;

    /**
     * @var EventLDProjector
     */
    protected $projector;

    /**
     * @var Serializer|MockObject
     */
    protected $serializer;

    /**
     * @var IriOfferIdentifierFactoryInterface|MockObject
     */
    protected $iriOfferIdentifierFactory;

    /**
     * @var CdbXMLImporter|MockObject
     */
    protected $cdbXMLImporter;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName, 'CultuurNet\\UDB3\\Event');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cdbXMLEventFactory = new CdbXMLEventFactory();

        $this->placeService = $this->createMock(PlaceService::class);

        $this->iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->serializer = new MediaObjectSerializer($this->iriGenerator);

        $this->iriOfferIdentifierFactory = $this->createMock(IriOfferIdentifierFactoryInterface::class);
        $this->cdbXMLImporter = new CdbXMLImporter(
            new CdbXMLItemBaseImporter(
                new PriceDescriptionParser(
                    new NumberFormatRepository(),
                    new CurrencyRepository()
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
            new CdbXmlContactInfoImporter()
        );

        $this->projector = new EventLDProjector(
            $this->documentRepository,
            $this->iriGenerator,
            $this->placeService,
            $this->organizerService,
            $this->serializer,
            $this->iriOfferIdentifierFactory,
            $this->cdbXMLImporter,
            new JsonDocumentLanguageEnricher(
                new EventJsonDocumentLanguageAnalyzer()
            ),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ]
        );
    }

    /**
     * @test
     */
    public function it_handles_new_events_without_theme()
    {
        $eventId = '1';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00')
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
    public function it_handles_new_events_with_theme()
    {
        $eventId = '1';
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00')
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
     *
     * @param Metadata $metadata
     * @param string $expectedCreator
     */
    public function it_handles_new_events_with_creator(Metadata $metadata, $expectedCreator)
    {
        $eventId = '1';
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00')
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
        $jsonLD->creator = $expectedCreator;

        $this->mockPlaceService();

        $body = $this->project(
            $eventCreated,
            $eventId,
            $metadata,
            DateTime::fromString('2015-01-20T13:25:21+01:00')
        );

        $this->assertEquals($jsonLD, $body);
    }

    /**
     * @return array
     */
    public function eventCreatorDataProvider()
    {
        return [
            [
                new Metadata(
                    [
                        'user_email' => 'foo@bar.com',
                        'user_nick' => 'foo',
                        'user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c',
                    ]
                ),
                '20a72430-7e3e-4b75-ab59-043156b3169c',
            ],
            [
                new Metadata(
                    [
                        'user_nick' => 'foo',
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
    public function it_handles_copy_event()
    {
        $originalEventId = '1';
        $originalCalendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00')
        );
        $eventCreated = $this->createEventCreated($originalEventId, $originalCalendar, null);

        $this->project($eventCreated, $originalEventId);

        $this->project(
            new Published($originalEventId, new \DateTime()),
            $originalEventId
        );

        $this->project(
            new LabelAdded($originalEventId, new Label('2dotstwice', true)),
            $originalEventId
        );

        $this->project(
            new LabelAdded($originalEventId, new Label('cultuurnet', false)),
            $originalEventId
        );

        $eventId = '2';
        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-27T13:25:21+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-28T13:25:21+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-29T13:25:21+01:00')
            ),
        ];
        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-29T13:25:21+01:00'),
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

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_copied_event_with_work_hours_removed()
    {
        $eventCreated = $this->createEventCreated('1', $this->aPeriodicCalendarWithWorkScheme(), null);

        $this->project($eventCreated, $eventCreated->getEventId());
        $this->project($this->aPublishedEvent($eventCreated), $eventCreated->getEventId());

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-29T13:25:21+01:00')
        );

        $eventCopied = new EventCopied('2', $eventCreated->getEventId(), $calendar);

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

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_handles_new_events_with_multiple_timestamps()
    {
        $eventId = '926fca95-010e-46b1-8b8e-abe757dd32d5';

        $timestamps = [
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-27T13:25:21+01:00')
            ),
            new Timestamp(
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-28T13:25:21+01:00'),
                \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-29T13:25:21+01:00')
            ),
        ];

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-29T13:25:21+01:00'),
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
                '@type' => 'Event',
                'startDate' => '2015-01-26T13:25:21+01:00',
                'endDate' => '2015-01-27T13:25:21+01:00',
            ],
            (object)[
                '@type' => 'Event',
                'startDate' => '2015-01-28T13:25:21+01:00',
                'endDate' => '2015-01-29T13:25:21+01:00',
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
    public function it_should_set_a_main_language_when_importing_cdbxml()
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
    public function it_should_not_change_main_language_when_updating()
    {
        // First make sure there is already an event, so it is a real update.
        $eventId = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00')
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

        $this->assertEquals(new Language('en'), $body->mainLanguage);
    }

    /**
     * @test
     */
    public function it_strips_empty_keywords_when_importing_from_udb2()
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
    public function it_does_remove_existing_location_when_updating_from_udb2_without_location_id()
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
        $this->assertEquals("Place", $body->location->{'@type'});
        $this->assertArrayNotHasKey(
            "@id",
            (array) $body->location
        );
        $this->assertArrayHasKey(
            "name",
            (array) $body->location
        );
        $this->assertArrayHasKey(
            "address",
            (array) $body->location
        );
    }

    /**
     * @test
     */
    public function it_can_update_an_event_from_udb2_even_if_it_has_been_deleted()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );
        $eventId = $event->getEventId();

        $this->project($event, $event->getEventId());

        $eventDeleted = new EventDeleted($eventId);

        $this->project($eventDeleted, $eventDeleted->getItemId(), null, null, false);

        $eventUpdatedFromUdb2 = $this->cdbXMLEventFactory->eventUpdatedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );
        $this->project($eventUpdatedFromUdb2, $eventUpdatedFromUdb2->getEventId());

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_labels_property()
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
    public function it_does_not_add_an_empty_image_property()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_without_image.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertObjectNotHasAttribute('image', $body);
    }

    /**
     * @test
     */
    public function it_adds_a_bookingInfo_property_when_cdbxml_has_pricevalue()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_price_value.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new \stdClass();
        $expectedBookingInfo->priceCurrency = 'EUR';
        $expectedBookingInfo->price = 0;

        $this->assertInternalType('object', $bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_adds_the_pricedescription_from_cdbxml_to_bookingInfo()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_price_value_and_description.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new \stdClass();
        $expectedBookingInfo->priceCurrency = 'EUR';
        $expectedBookingInfo->price = 0;
        $expectedBookingInfo->description = 'Gratis voor iedereen!';

        $this->assertInternalType('object', $bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_does_not_add_a_missing_price_from_cdbxml_to_bookingInfo()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_only_price_description.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $bookingInfo = $body->bookingInfo;

        $expectedBookingInfo = new stdClass();
        $expectedBookingInfo->description = 'Gratis voor iedereen!';

        $this->assertInternalType('object', $bookingInfo);
        $this->assertEquals($expectedBookingInfo, $bookingInfo);
    }

    /**
     * @test
     */
    public function it_does_not_add_booking_info_when_price_and_reservation_contact_channels_are_missing()
    {
        $event = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_without_price.cdbxml.xml'
        );

        $body = $this->project($event, $event->getEventId());

        $this->assertObjectNotHasAttribute('bookingInfo', $body);
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label()
    {
        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
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
    public function it_projects_the_removal_of_a_label()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'labels' => ['label A', 'label B', 'label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            new Label('label B')
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
    public function it_projects_the_addition_of_a_label_to_an_event_without_existing_labels()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'bar' => 'stool',
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = new stdClass();
        $expectedBody->bar = 'stool';
        $expectedBody->labels = ['label B'];
        $expectedBody->modified = $this->recordedOn->toString();

        $this->assertEquals(
            $expectedBody,
            $body
        );

    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_major_info()
    {
        $this->mockPlaceService();

        $id = 'foo';
        $title = new Title('new title');
        $eventType = new EventType('0.50.4.0.1', 'concertnew');
        $location = new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e');
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-02-26T13:25:21+01:00')
        );
        $theme = new Theme('123', 'theme label');
        $majorInfoUpdated = new MajorInfoUpdated($id, $title, $eventType, $location, $calendar, $theme);

        $jsonLD = new stdClass();
        $jsonLD->id = $id;
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = ['en' => 'some representative title'];
        $jsonLD->location = [
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
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
        $expectedJsonLD->location = (object)[
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
        $expectedJsonLD->calendarType = 'single';
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

        $body = $this->project($majorInfoUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_calendar_updated()
    {
        $eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
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
        $expectedJsonLD->calendarType = 'single';
        $expectedJsonLD->startDate = '2020-01-26T11:11:11+01:00';
        $expectedJsonLD->endDate = '2020-01-27T12:12:12+01:00';
        $expectedJsonLD->availableTo = '2020-01-27T12:12:12+01:00';
        $expectedJsonLD->modified = $this->recordedOn->toString();

        $body = $this->project($calendarUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_location()
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

        $body = $this->project($locationUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_geo_coordinates()
    {
        $id = 'ea328f14-a3c8-4f71-abd9-00cd0a2cf217';

        $initialDocument = new JsonDocument(
            $id,
            json_encode(
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
        ];

        $body = $this->project($coordinatesUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @group issue-III-1730
     * @test
     */
    public function it_keeps_alien_terms_imported_from_udb2_when_updating_major_info()
    {
        $importedFromUDB2 = $this->cdbXMLEventFactory->eventImportedFromUDB2(
            'samples/event_with_empty_keyword.cdbxml.xml'
        );

        $title = new Title('new title');
        $eventType = new EventType('0.50.4.0.1', 'concertnew');
        $location = new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e');
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-02-26T13:25:21+01:00')
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
    public function it_projects_updating_audience()
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $audienceUpdated = new AudienceUpdated(
            $eventId,
            new Audience(AudienceType::EDUCATION())
        );

        $body = $this->project($audienceUpdated, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
                '@id' => 'http://example.com/entity/' . $eventId,
                '@context' => '/contexts/event',
                'audience' => (object) ['audienceType' => 'education'],
                'modified' => $this->recordedOn->toString(),
            ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_updates_workflow_status_on_delete()
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $eventDeleted = new EventDeleted($eventId);

        $body = $this->project($eventDeleted, $eventId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $eventId,
            '@context' => '/contexts/event',
            'workflowStatus' => 'DELETED',
            'modified' => $this->recordedOn->toString(),
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     * @dataProvider eventUpdateDataProvider
     * @param $documentWithUDB3Media
     * @param $domainMessage
     * @param $expectedMediaObjects
     */
    public function it_prioritizes_udb3_media_when_updating_an_event(
        $documentWithUDB3Media,
        $domainMessage,
        $expectedMediaObjects
    ) {
        $this->documentRepository->save($documentWithUDB3Media);

        $this->projector->handle($domainMessage);

        $this->assertEquals(
            $expectedMediaObjects,
            $this->documentRepository->get(CdbXMLEventFactory::AN_EVENT_ID)->getBody()->mediaObject
        );
    }

    public function eventUpdateDataProvider()
    {
        $documentWithUDB3Media = new JsonDocument(
            CdbXMLEventFactory::AN_EVENT_ID,
            json_encode([
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
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
                'description' => 'sexy ladies without clothes',
                'copyrightHolder' => 'Bart Ramakers',
            ],
        ];

        $importedDate = '2015-03-01T10:17:19.176169+02:00';

        $metadata = array();
        $metadata['user_nick'] = 'Jantest';
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

    /**
     * @param string $eventId
     * @param Calendar $calendar
     * @param Theme|null $theme
     * @return EventCreated
     */
    private function createEventCreated(
        $eventId,
        Calendar $calendar,
        Theme $theme = null
    ) {
        return new EventCreated(
            $eventId,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            $calendar,
            $theme
        );
    }

    /**
     * @param string $eventId
     * @param Language $mainLanguage
     * @return stdClass
     */
    private function createJsonLD($eventId, Language $mainLanguage)
    {
        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/'. $eventId;
        $jsonLD->{'@context'} = '/contexts/event';
        $jsonLD->mainLanguage = $mainLanguage->getCode();
        $jsonLD->name = (object)[
            $mainLanguage->getCode() => 'some representative title',
        ];
        $jsonLD->location = (object)[
            '@type' => 'Place',
            '@id' => 'http://example.com/entity/395fe7eb-9bac-4647-acae-316b6446a85e',
        ];
        $jsonLD->calendarType = 'single';
        $jsonLD->startDate = '2015-01-26T13:25:21+01:00';
        $jsonLD->availableTo = $jsonLD->startDate;
        $jsonLD->sameAs = [
            'http://www.uitinvlaanderen.be/agenda/e/some-representative-title/1',
        ];
        $jsonLD->created = '2015-01-20T13:25:21+01:00';
        $jsonLD->modified = '2015-01-20T13:25:21+01:00';
        $jsonLD->workflowStatus = 'DRAFT';
        $jsonLD->audience = (object)['audienceType' => 'everyone'];
        $jsonLD->languages = [$mainLanguage->getCode()];
        $jsonLD->completedLanguages = [$mainLanguage->getCode()];

        return $jsonLD;
    }

    private function mockPlaceService()
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

    /**
     * @return Calendar
     */
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
}
