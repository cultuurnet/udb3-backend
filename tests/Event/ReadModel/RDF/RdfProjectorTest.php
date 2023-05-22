<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\ParsedAddress;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\ExternalId\MappingServiceInterface;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\DummyLocationUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\ExternalIdLocationUpdated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\DummyLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title as LegacyTitle;
use DateTime;
use DateTimeImmutable;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private RdfProjector $rdfProjector;

    private GraphRepository $graphRepository;

    /**
     * @var AddressParser|MockObject
     */
    private $addressParser;

    /**
     * @var MappingServiceInterface|MockObject
     */
    private $mappingService;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();

        $this->addressParser = $this->createMock(AddressParser::class);

        $this->mappingService = $this->createMock(MappingServiceInterface::class);

        $this->rdfProjector = new RdfProjector(
            new InMemoryMainLanguageRepository(),
            $this->graphRepository,
            new InMemoryLocationIdRepository(),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->addressParser,
            $this->mappingService
        );
    }

    /**
     * @test
     */
    public function it_handles_event_created(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/created.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_title_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleUpdated($eventId, new LegacyTitle('Faith no more in concert')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_title_translated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleTranslated($eventId, new Language('de'), new LegacyTitle('Faith no more im Konzert')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_multiple_title_translated_and_title_updated_events(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleTranslated($eventId, new Language('de'), new LegacyTitle('Faith no more im Konzert')),
            new TitleUpdated($eventId, new LegacyTitle('Faith no more im concert')),
            new TitleTranslated($eventId, new Language('de'), new LegacyTitle('Faith no more im Konzert [UPDATED]')),
            new TitleUpdated($eventId, new LegacyTitle('Faith no more in concert [UPDATED]')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-updated-and-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_published(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Published($eventId, new DateTime('2023-04-23T12:30:15+02:00')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/published.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_approved(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Approved($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/approved.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_rejected(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Rejected($eventId, new StringLiteral('This is not a valid event')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_flagged_as_duplicate(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new FlaggedAsDuplicate($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_flagged_as_inappropriate(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new FlaggedAsInappropriate($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_deleted(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new EventDeleted($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/deleted.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_description_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DescriptionUpdated($eventId, new Description('Dit is het laatste concert van Faith no more')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/description-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_description_updated_with_empty_string(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DescriptionUpdated($eventId, new Description('Dit is het laatste concert van Faith no more')),
            new DescriptionUpdated($eventId, new Description('')),
            new DescriptionUpdated($eventId, new Description('Ze geven nog een extra concert')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/description-updated-with-empty-string.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_description_translated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DescriptionUpdated($eventId, new Description('Dit is het laatste concert van Faith no more')),
            new DescriptionTranslated($eventId, new Language('en'), new Description('This will be the last concert of Faith no more')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/description-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_location_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new LocationUpdated($eventId, new LocationId('ee4300a6-82a0-4489-ada0-1a6be1fca442')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_location_updated_on_event_created_with_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                    ]
                )
            ),
            new LocationUpdated($eventId, new LocationId('ee4300a6-82a0-4489-ada0-1a6be1fca442')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/location-updated-on-calendar-single.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_external_id_location_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->mappingService->expects($this->once())
            ->method('getCdbId')
            ->with('external_id')
            ->willReturn('498dab67-236e-4bdb-9a70-7c26ad75301f');

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new ExternalIdLocationUpdated($eventId, 'external_id'),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/external-id-location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_dummy_location_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Martelarenlaan',
                    '1',
                    '3000',
                    'Leuven'
                )
            );

        $this->project($eventId, [
            new DummyLocationUpdated(
                $eventId,
                new DummyLocation(
                    new Title('Het Depot Leuven'),
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    )
                )
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/dummy-location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_dummy_location_updated_with_no_parsed_address(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(null);

        $this->project($eventId, [
            new DummyLocationUpdated(
                $eventId,
                new DummyLocation(
                    new Title('Het Depot Leuven'),
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    )
                )
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/dummy-location-updated-no-parsed-address.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_location_updated_after_dummy_location_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Martelarenlaan',
                    '1',
                    '3000',
                    'Leuven'
                )
            );

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DummyLocationUpdated(
                $eventId,
                new DummyLocation(
                    new Title('Het Depot Leuven'),
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    )
                )
            ),
            new LocationUpdated($eventId, new LocationId('ee4300a6-82a0-4489-ada0-1a6be1fca442')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/location-updated-after-dummy-location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_after_dummy_location_updated(): void
    {
        $eventId = '253f6304-13e8-4fda-897f-955df3bbecd2';

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Martelarenlaan',
                    '1',
                    '3000',
                    'Leuven'
                )
            );

        $this->project($eventId, [
            new DummyLocationUpdated(
                $eventId,
                new DummyLocation(
                    new Title('Het Depot Leuven'),
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    )
                ),
            ),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-02T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-multiple-after-dummy-location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_location_updated_after_calendar_updated_after_dummy_location_updated(): void
    {
        $eventId = '253f6304-13e8-4fda-897f-955df3bbecd2';

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Martelarenlaan',
                    '1',
                    '3000',
                    'Leuven'
                )
            );

        $this->project($eventId, [
            new DummyLocationUpdated(
                $eventId,
                new DummyLocation(
                    new Title('Het Depot Leuven'),
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    )
                ),
            ),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-02T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
            new LocationUpdated($eventId, new LocationId('ee4300a6-82a0-4489-ada0-1a6be1fca442')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/location-updated-after-calendar-updated-multiple-after-dummy-location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_event_created_with_periodic_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated(
                $eventId,
                new Calendar(
                    CalendarType::PERIODIC(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2022-01-01T17:00:00+01:00'),
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/created-with-calendar-periodic.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_event_created_with_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00');

        $this->project($eventId, [
            $this->getEventCreated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    $startDate,
                    $endDate,
                    [
                        new Timestamp($startDate, $endDate),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/created-with-calendar-single.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_event_created_with_multiple_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-02T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/created-with-calendar-multiple.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_permanent(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00');

        $this->project($eventId, [
            $this->getEventCreated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    $startDate,
                    $endDate,
                    [
                        new Timestamp($startDate, $endDate),
                    ]
                ),
            ),
            new CalendarUpdated($eventId, new Calendar(CalendarType::PERMANENT())),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-permanent.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_periodic(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::PERIODIC(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2022-01-01T17:00:00+01:00'),
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-periodic.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_periodic_without_event_created(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::PERIODIC(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2022-01-01T17:00:00+01:00'),
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-periodic-without-event-created.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_single(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00');

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    $startDate,
                    $endDate,
                    [
                        new Timestamp($startDate, $endDate),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-single.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_multiple(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-02T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-multiple.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_various_calendar_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-03T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-03T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-03T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/various-calendar-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_type_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TypeUpdated($eventId, new EventType('1.8.3.1.0', 'Pop en rock')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/type-updated.ttl'));
    }

    private function getEventCreated(string $eventId, ?Calendar $calendar = null): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            new LegacyTitle('Faith no more'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            $calendar ?: new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );
    }

    private function project(string $placeId, array $events): void
    {
        $playhead = -1;
        $recordedOn = new DateTime('2022-12-31T12:30:15+01:00');
        foreach ($events as $event) {
            $playhead++;
            $recordedOn->modify('+1 day');
            $domainMessage = new DomainMessage(
                $placeId,
                $playhead,
                new Metadata(),
                $event,
                BroadwayDateTime::fromString($recordedOn->format(DateTime::ATOM))
            );
            $this->rdfProjector->handle($domainMessage);
        }
    }

    private function assertTurtleData(string $placeId, string $expectedTurtleData): void
    {
        $uri = 'https://mock.data.publiq.be/events/' . $placeId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
