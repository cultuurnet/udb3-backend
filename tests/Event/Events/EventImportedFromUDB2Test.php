<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\ValueObjects\DummyLocation;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Calendar\Timestamp;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SampleFiles;
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
            SampleFiles::read(__DIR__ . '/../samples/event_entryapi_valid_with_keywords.xml'),
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
        array $expectedSerializedValue,
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
        array $serializedValue,
        EventImportedFromUDB2 $expectedEventImportedFromUDB2
    ): void {
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
            SampleFiles::read(__DIR__ . '/../samples/event_with_udb3_place.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', 'Blubblub'),
                new TypeUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new EventType('0.3.1.0.0', 'Cursus of workshop')
                ),
                new DummyLocationUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new DummyLocation(
                        new Title('Test locatie 2099'),
                        new Address(
                            new Street('teststraat 44'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            new CountryCode('BE')
                        )
                    )
                ),
                new CalendarUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Calendar(
                        CalendarType::single(),
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
            SampleFiles::read(__DIR__ . '/../samples/event_with_translations.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Het evenement!'),
                new TitleTranslated($eventId, new Language('fr'), 'L\'événement!'),
                new TitleTranslated($eventId, new Language('de'), 'Das Ereignis!'),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new DummyLocationUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new DummyLocation(
                        new Title('Test locatie 2099'),
                        new Address(
                            new Street('teststraat 44'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            new CountryCode('BE')
                        )
                    )
                ),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::single(),
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
            SampleFiles::read(__DIR__ . '/../samples/event_with_existing_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Het evenement!'),
                new TitleTranslated($eventId, new Language('fr'), 'L\'événement!'),
                new TitleTranslated($eventId, new Language('de'), 'Das Ereignis!'),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new LocationUpdated($eventId, new LocationId('28d2900d-f784-4d04-8d66-5b93900c6f9c')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::single(),
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
    public function it_can_convert_events_with_external_id_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/../samples/event_with_externalid_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Het evenement!'),
                new TitleTranslated($eventId, new Language('fr'), 'L\'événement!'),
                new TitleTranslated($eventId, new Language('de'), 'Das Ereignis!'),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new ExternalIdLocationUpdated($eventId, 'SKB:9ccbf9c1-a5c5-4689-9687-9a7dd3c51aee'),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::single(),
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
    public function it_returns_an_external_id_if_present(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithExternalIdLocation = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/../samples/event_with_externalid_location.cdbxml.xml'),
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
    public function it_can_convert_a_periodic_event_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/../samples/calendar/event_with_periodic_calendar_and_week_schema.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Oscar et la Dame Rose'),
                new TitleTranslated($eventId, new Language('fr'), 'Oscar et la Dame Rose'),
                new TitleTranslated($eventId, new Language('en'), 'Oscar et la Dame Rose'),
                new TypeUpdated($eventId, new EventType('0.55.0.0.0', 'Theatervoorstelling')),
                new DummyLocationUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new DummyLocation(
                        new Title('La Flûte Enchantée'),
                        new Address(
                            new Street('rue du Printemps 18'),
                            new PostalCode('1050'),
                            new Locality('Elsene'),
                            new CountryCode('BE')
                        )
                    )
                ),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::periodic(),
                        DateTimeFactory::fromFormat('Y-m-d', '2017-06-13'),
                        DateTimeFactory::fromFormat('Y-m-d', '2018-01-08'),
                        [],
                        [
                            0 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::monday())
                            ),
                            1 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::tuesday())
                            ),
                            2 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::wednesday())
                            ),
                            3 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::thursday())
                            ),
                            4 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::friday())
                            ),
                            5 => new OpeningHour(
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(18), new Minute(0)),
                                new Days(Day::saturday())
                            ),
                            6 => new OpeningHour(
                                new Time(new Hour(8), new Minute(0)),
                                new Time(new Hour(12), new Minute(0)),
                                new Days(Day::sunday())
                            ),
                        ]
                    )
                ),
                new Published(
                    $eventId,
                    DateTimeFactory::fromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
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
            SampleFiles::read(__DIR__ . '/../samples/calendar/event_with_permanent_calendar_and_opening_hours.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Werken met de \'nailliner\''),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new DummyLocationUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new DummyLocation(
                        new Title('Nagelstudio Vanderbeken'),
                        new Address(
                            new Street('Frère-Orbanstraat 159'),
                            new PostalCode('8400'),
                            new Locality('Oostende'),
                            new CountryCode('BE')
                        )
                    )
                ),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::permanent(),
                        null,
                        null,
                        [],
                        [
                            0 => new OpeningHour(
                                new Time(new Hour(9), new Minute(30)),
                                new Time(new Hour(11), new Minute(30)),
                                new Days(Day::wednesday())
                            ),
                            1 => new OpeningHour(
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0)),
                                new Days(Day::thursday())
                            ),
                            2 => new OpeningHour(
                                new Time(new Hour(9), new Minute(30)),
                                new Time(new Hour(11), new Minute(30)),
                                new Days(Day::saturday())
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
            SampleFiles::read(__DIR__ . '/../samples/calendar/event_with_multiple_timestamps_and_start_times.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Juwelen maken VOORJAAR 2017'),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new DummyLocationUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new DummyLocation(
                        new Title('Wijkcentrum Condé'),
                        new Address(
                            new Street('Condédreef(Kor) 16'),
                            new PostalCode('8500'),
                            new Locality('Kortrijk'),
                            new CountryCode('BE')
                        )
                    )
                ),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::multiple(),
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
                    DateTimeFactory::fromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
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
            SampleFiles::read(__DIR__ . '/../samples/calendar/event_with_timestamp_and_start_time.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'De Smoestuinier | Low Impact man'),
                new TypeUpdated($eventId, new EventType('0.55.0.0.0', 'Theatervoorstelling')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::single(),
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
                    DateTimeFactory::fromFormat('Y-m-d\TH:i:s', '2016-11-18T07:44:11', new DateTimeZone('Europe/Brussels'))
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_a_deleted_event_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/../samples/event_with_workflow_deleted.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Punt sparen'),
                new TypeUpdated($eventId, new EventType('0.0.0.0.0', 'Tentoonstelling')),
                new LocationUpdated($eventId, new LocationId('66b69120-45d2-4b3d-a34c-aca115ebc2f0')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::permanent(),
                        null,
                        null,
                        []
                    )
                ),
                new EventDeleted($eventId),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    /**
    * @test
    */
    public function it_can_convert_a_rejected_event_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId,
            SampleFiles::read(__DIR__ . '/../samples/event_with_workflow_rejected.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Punt sparen'),
                new TypeUpdated($eventId, new EventType('0.0.0.0.0', 'Tentoonstelling')),
                new LocationUpdated($eventId, new LocationId('66b69120-45d2-4b3d-a34c-aca115ebc2f0')),
                new CalendarUpdated(
                    $eventId,
                    new Calendar(
                        CalendarType::permanent(),
                        null,
                        null,
                        []
                    )
                ),
                new Rejected(
                    $eventId,
                    'Reason unknown (imported from UiTdatabank v2)'
                ),
            ],
            $eventImportedFromUDB2->toGranularEvents()
        );
    }

    public function serializationDataProvider(): array
    {
        $xml = SampleFiles::read(__DIR__ . '/../samples/event_entryapi_valid_with_keywords.xml');

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
