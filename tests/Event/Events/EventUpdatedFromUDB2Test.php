<?php

declare(strict_types=1);

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
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
        $expectedSerializedValue,
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
        $serializedValue,
        EventUpdatedFromUDB2 $expectedEventUpdatedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedEventUpdatedFromUDB2,
            EventUpdatedFromUDB2::deserialize($serializedValue)
        );
    }

    public function it_can_be_converted_to_granular_events(
        $serializedValue,
        EventUpdatedFromUDB2 $expectedEventUpdatedFromUDB2
    ): void {
        $eventId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $eventImportedFromUDB2 = new EventUpdatedFromUDB2(
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
