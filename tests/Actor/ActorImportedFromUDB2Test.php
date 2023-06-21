<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Actor;

use PHPUnit\Framework\TestCase;

class ActorImportedFromUDB2Test extends TestCase
{
    public const NS_CDBXML_3_2 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';
    public const NS_CDBXML_3_3 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        ActorImportedFromUDB2 $actorImportedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $actorImportedFromUDB2->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        ActorImportedFromUDB2 $expectedActorImportedFromUDB2
    ): void {
        $this->assertEquals(
            $expectedActorImportedFromUDB2,
            ActorImportedFromUDB2::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
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

    public function serializationDataProvider(): array
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
