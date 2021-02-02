<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class ContactPointUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param ContactPointUpdated $contactPointUpdated
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        ContactPointUpdated $contactPointUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $contactPointUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param ContactPointUpdated $expectedContactPointUpdated
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        ContactPointUpdated $expectedContactPointUpdated
    ) {
        $this->assertEquals(
            $expectedContactPointUpdated,
            ContactPointUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            'contactPointUpdated' => [
                [
                    'item_id' => 'foo',
                    'contactPoint' => [
                        'phone' => [
                            '0123456789',
                            ],
                        'email' => [
                            'foo@bar.com',
                            ],
                        'url' => [
                            'http://foo.bar',
                            ],
                    ],
                ],
                new ContactPointUpdated(
                    'foo',
                    new ContactPoint(
                        ['0123456789'],
                        ['foo@bar.com'],
                        ['http://foo.bar']
                    )
                ),
            ],
        ];
    }
}
