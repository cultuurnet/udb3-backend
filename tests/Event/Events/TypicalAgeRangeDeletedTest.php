<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class TypicalAgeRangeDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        TypicalAgeRangeDeleted $typicalAgeRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalAgeRangeDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        TypicalAgeRangeDeleted $expectedTypicalAgeRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedTypicalAgeRangeDeleted,
            TypicalAgeRangeDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'typical age range' => [
                [
                    'item_id' => 'foo',
                ],
                new TypicalAgeRangeDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
