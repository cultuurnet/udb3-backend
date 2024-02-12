<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\ValueObjects\DummyLocation;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Calendar\Timestamp;
use PHPUnit\Framework\TestCase;

final class EventUpdatedFromUDB2Test extends TestCase
{
    public const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    public const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $eventUpdatedFromUDB2->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        EventUpdatedFromUDB2 $expectedEventUpdatedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedEventUpdatedFromUDB2,
            EventUpdatedFromUDB2::deserialize($serializedValue)
        );
    }

    public function it_can_be_converted_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventUpdatedFromUdb2 = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_translations.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($eventId, 'Het evenement!'),
                new TitleTranslated($eventId, new Language('fr'), 'L\'événement!'),
                new TitleTranslated($eventId, new Language('de'), 'Das Ereignis!'),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
            ],
            $eventUpdatedFromUdb2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_events_with_location_id_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventUpdatedFromUdb2 = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_existing_location.cdbxml.xml'),
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
            $eventUpdatedFromUdb2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_events_with_external_id_to_granular_events(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventUpdatedFromUdb2 = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_externalid_location.cdbxml.xml'),
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
            $eventUpdatedFromUdb2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_returns_a_dummy_location(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithDummyLocation = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_dummy_location.cdbxml.xml'),
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
    public function it_returns_a_dummy_location_without_street(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithDummyLocation = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_dummy_location_without_street.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            new DummyLocation(
                new Title('Liberaal Archief'),
                new Address(
                    new Street('23'),
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
    public function it_returns_a_dummy_location_without_number(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithDummyLocation = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_dummy_location_without_number.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            new DummyLocation(
                new Title('Liberaal Archief'),
                new Address(
                    new Street('Kramersplein'),
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
    public function it_returns_a_dummy_location_without_street_and_number(): void
    {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventWithDummyLocation = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_dummy_location_without_street_and_number.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            new DummyLocation(
                new Title('Liberaal Archief'),
                new Address(
                    new Street('-'),
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
        $eventWithExternalIdLocation = new EventUpdatedFromUDB2(
            $eventId,
            file_get_contents(__DIR__ . '/../samples/event_with_externalid_location.cdbxml.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            'SKB:9ccbf9c1-a5c5-4689-9687-9a7dd3c51aee',
            $eventWithExternalIdLocation->getExternalId()
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
                new EventUpdatedFromUDB2(
                    'test 456',
                    $xml,
                    self::NS_CDBXML_3_3
                ),
            ],
        ];
    }
}
