<?php

namespace CultuurNet\UDB3\Actor;

use PHPUnit\Framework\TestCase;

class ActorImportedFromUDB2Test extends TestCase
{
    const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param ActorImportedFromUDB2 $actorImportedFromUDB2
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        ActorImportedFromUDB2 $actorImportedFromUDB2
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $actorImportedFromUDB2->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param ActorImportedFromUDB2 $expectedActorImportedFromUDB2
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        ActorImportedFromUDB2 $expectedActorImportedFromUDB2
    ) {
        $this->assertEquals(
            $expectedActorImportedFromUDB2,
            ActorImportedFromUDB2::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedCdbXml = 'cdbxml';
        $expectedCdbXmlNamespace = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

        $actorImportedFromUDB2 = new ActorImportedFromUDB2(
            'actor_id',
            'cdbxml',
            self::NS_CDBXML_3_2
        );

        $this->assertEquals($expectedCdbXml, $actorImportedFromUDB2->getCdbXml());
        $this->assertEquals($expectedCdbXmlNamespace, $actorImportedFromUDB2->getCdbXmlNamespaceUri());
    }

    public function serializationDataProvider()
    {
        return [
            'actorImportedFromUDB2' => [
                [
                    'actor_id' => 'actor_id',
                    'cdbxml' => 'cdbxml',
                    'cdbXmlNamespaceUri' => 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL',
                ],
                new ActorImportedFromUDB2(
                    'actor_id',
                    'cdbxml',
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
                ),
            ],
        ];
    }
}
