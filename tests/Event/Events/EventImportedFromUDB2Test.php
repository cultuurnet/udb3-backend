<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use PHPUnit\Framework\TestCase;

class EventImportedFromUDB2Test extends TestCase
{
    const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        EventImportedFromUDB2 $eventImportedFromUDB2
    ) {
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

    public function serializationDataProvider()
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
