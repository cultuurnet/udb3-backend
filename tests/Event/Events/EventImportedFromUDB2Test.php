<?php

declare(strict_types=1);

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
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
                new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', new Title('Blubblub')),
                new TypeUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new EventType('0.3.1.0.0', 'Cursus of workshop')
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
                new TitleUpdated($eventId, new Title('Het evenement!')),
                new TitleTranslated($eventId, new Language('fr'), new Title('L\'événement!')),
                new TitleTranslated($eventId, new Language('de'), new Title('Das Ereignis!')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
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
                new TitleUpdated($eventId, new Title('Het evenement!')),
                new TitleTranslated($eventId, new Language('fr'), new Title('L\'événement!')),
                new TitleTranslated($eventId, new Language('de'), new Title('Das Ereignis!')),
                new TypeUpdated($eventId, new EventType('0.3.1.0.0', 'Cursus of workshop')),
                new LocationUpdated($eventId, new LocationId('28d2900d-f784-4d04-8d66-5b93900c6f9c')),
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
