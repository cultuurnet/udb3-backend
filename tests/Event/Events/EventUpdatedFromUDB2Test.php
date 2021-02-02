<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use PHPUnit\Framework\TestCase;

class EventUpdatedFromUDB2Test extends TestCase
{
    const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ) {
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
    ) {
        $this->assertEquals(
            $expectedEventUpdatedFromUDB2,
            EventUpdatedFromUDB2::deserialize($serializedValue)
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
                new EventUpdatedFromUDB2(
                    'test 456',
                    $xml,
                    self::NS_CDBXML_3_3
                ),
            ],
        ];
    }
}
