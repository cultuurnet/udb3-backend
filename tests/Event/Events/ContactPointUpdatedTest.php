<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\ContactPoint;
use PHPUnit\Framework\TestCase;

class ContactPointUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        ContactPointUpdated $contactPointUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $contactPointUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        ContactPointUpdated $expectedContactPointUpdated
    ): void {
        $this->assertEquals(
            $expectedContactPointUpdated,
            ContactPointUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
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
