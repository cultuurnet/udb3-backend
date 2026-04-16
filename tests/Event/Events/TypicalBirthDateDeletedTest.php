<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class TypicalBirthDateDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        TypicalBirthDateDeleted $typicalBirthDateDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalBirthDateDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        TypicalBirthDateDeleted $expectedTypicalBirthDateDeleted
    ): void {
        $this->assertEquals(
            $expectedTypicalBirthDateDeleted,
            TypicalBirthDateDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'typical birth date' => [
                [
                    'item_id' => 'foo',
                ],
                new TypicalBirthDateDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
