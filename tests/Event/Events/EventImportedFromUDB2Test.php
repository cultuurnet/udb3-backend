<?php

declare(strict_types=1);

namespace test\Event\Events;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DummyLocation;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title as LegacyTitle;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class EventImportedFromUDB2Test extends TestCase
{
    public const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    public const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     */
    public function it_implements_main_language_defined(): void
    {
        $event = new EventImportedFromUDB2(
            'test 456',
            file_get_contents(__DIR__ . '/../samples/event_entryapi_valid_with_keywords.xml'),
            self::NS_CDBXML_3_3
        );

        $this->assertInstanceOf(MainLanguageDefined::class, $event);
        $this->assertEquals(new Language('nl'), $event->getMainLanguage());
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        EventImportedFromUDB2 $eventImportedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $eventImportedFromUDB2->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        EventImportedFromUDB2 $expectedEventImportedFromUDB2
    ) {
        $this->assertEquals(
            $expectedEventImportedFromUDB2,
            EventImportedFromUDB2::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_can_convert_to_granular_events(): void
    {
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
            file_get_contents(__DIR__ . '/../samples/event_with_udb3_place.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', new LegacyTitle('Blubblub')),
                new TypeUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new EventType('0.3.1.0.0', 'Cursus of workshop')
                ),
                new CalendarUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Calendar(
                        CalendarType::SINGLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200'),
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200')
                            ),
                        ]
                    )
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_translated_events_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_translations.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('Het evenement!')),
                new TitleTranslated($eventId, new Language('fr'), new LegacyTitle('L\'événement!')),
                new TitleTranslated($eventId, new Language('de'), new LegacyTitle('Das Ereignis!')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::SINGLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200'),
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200')
                            ),
                        ]
                    )
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }


    /**
     * @test
     */
    public function it_can_convert_events_with_location_id_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_existing_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('Het evenement!')),
                new TitleTranslated($eventId, new Language('fr'), new LegacyTitle('L\'événement!')),
                new TitleTranslated($eventId, new Language('de'), new LegacyTitle('Das Ereignis!')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new LocationUpdated($eventId, new LocationId('28d2900d-f784-4d04-8d66-5b93900c6f9c')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::SINGLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200'),
                                new \DateTimeImmutable('2016-04-13T00:00:00.000000+0200')
                            ),
                        ]
                    )
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }


    /**
     * @test
     */
    public function it_does_not_return_a_dummy_location_if_location_id_is_present(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithLocationId = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_existing_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertNull($eventWithLocationId->getDummyLocation());
    }

    /**
     * @test
     */
    public function it_returns_a_dummy_location_(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithDummyLocation = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_photo.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            new DummyLocation(
                new Title('Liberaal Archief'),
                new Address(
                    new Street('Kramersplein 23'),
                    new PostalCode('9000'),
                    new Locality('Gent'),
                    new CountryCode('BE')
                )
            ),
            $eventWithDummyLocation->getDummyLocation()
        );
    }

    /**
     * @test
     */
    public function it_returns_an_external_id_if_present(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithExternalIdLocation = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_externalid_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            'SKB:9ccbf9c1-a5c5-4689-9687-9a7dd3c51aee',
            $eventWithExternalIdLocation->getExternalId()
        );
    }

    /**
     * @test
     */
    public function it_returns_null_if_no_external_id_is_present(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithExternalIdLocation = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_existing_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertNull($eventWithExternalIdLocation->getExternalId());
    }

    /**
     * @test
     */
    public function it_can_convert_a_periodic_event_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/calendar/event_with_periodic_calendar_and_week_schema.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('Oscar et la Dame Rose')),
                new TitleTranslated($eventId, new Language('fr'), new LegacyTitle('Oscar et la Dame Rose')),
                new TitleTranslated($eventId, new Language('en'), new LegacyTitle('Oscar et la Dame Rose')),
                new TypeUpdated($eventId, new EventType('0.55.0.0.0', 'Theatervoorstelling')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::PERIODIC(),
                        \DateTimeImmutable::createFromFormat('Y-m-d', '2017-06-13'),
                        \DateTimeImmutable::createFromFormat('Y-m-d', '2018-01-08'),
                        [],
                        [
                            0 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::MONDAY())
                            ),
                            1 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::TUESDAY())
                            ),
                            2 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::WEDNESDAY())
                            ),
                            3 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::THURSDAY())
                            ),
                            4 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::FRIDAY())
                            ),
                            5 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(10), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(18), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::SATURDAY())
                            ),
                            6 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(8), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(12), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::SUNDAY())
                            ),
                        ]
                    )
                ),
                new Published(
                    $eventId,
                    DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_a_permanent_event_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/calendar/event_with_permanent_calendar_and_opening_hours.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('Werken met de \'nailliner\'')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::PERMANENT(),
                        null,
                        null,
                        [],
                        [
                            0 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(9), new Minute(30)),
                                new Calendar\OpeningTime(new Hour(11), new Minute(30)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::WEDNESDAY())
                            ),
                            1 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(9), new Minute(0)),
                                new Calendar\OpeningTime(new Hour(17), new Minute(0)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::THURSDAY())
                            ),
                            2 => new Calendar\OpeningHour(
                                new Calendar\OpeningTime(new Hour(9), new Minute(30)),
                                new Calendar\OpeningTime(new Hour(11), new Minute(30)),
                                new Calendar\DayOfWeekCollection(Calendar\DayOfWeek::SATURDAY())
                            ),
                        ]
                    )
                ),
                new Approved($eventId),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_event_with_timestamps_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/calendar/event_with_multiple_timestamps_and_start_times.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('Juwelen maken VOORJAAR 2017')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new \DateTimeImmutable('2017-02-06T13:00:00.000000+0100'),
                                new \DateTimeImmutable('2017-02-06T13:00:00.000000+0100')
                            ),
                            new Timestamp(
                                new \DateTimeImmutable('2017-02-20T13:00:00.000000+0100'),
                                new \DateTimeImmutable('2017-02-20T13:00:00.000000+0100')
                            ),
                            new Timestamp(
                                new \DateTimeImmutable('2017-03-06T13:00:00.000000+0100'),
                                new \DateTimeImmutable('2017-03-06T13:00:00.000000+0100')
                            ),
                            new Timestamp(
                                new \DateTimeImmutable('2017-03-20T13:00:00.000000+0100'),
                                new \DateTimeImmutable('2017-03-20T13:00:00.000000+0100')
                            ),
                        ]
                    )
                ),
                new Published(
                    $eventId,
                    DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_event_with_a_single_timestamps_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/calendar/event_with_timestamp_and_start_time.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, new LegacyTitle('De Smoestuinier | Low Impact man')),
                new TypeUpdated($eventId, new EventType('0.55.0.0.0', 'Theatervoorstelling')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::SINGLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new \DateTimeImmutable('2017-04-27T20:15:00.000000+0200'),
                                new \DateTimeImmutable('2017-04-27T20:15:00.000000+0200')
                            ),
                        ]
                    )
                ),
                new Published(
                    $eventId,
                    DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    public function serializationDataProvider(): array
    {
        $xml = file_get_contents(__DIR__ . '/../samples/event_entryapi_valid_with_keywords.xml');

        return [
            'event' => [
                [
                    'event_id' => 'test 456',
                    'cdbxml' => $xml,
                    'cdbXmlNamespaceUri' => 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
                ],
                new EventImportedFromUDB2(
                    'test 456',
                    $xml,
                    self::NS_CDBXML_3_3
                ),
            ],
        ];
    }
}
