<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use PHPUnit\Framework\TestCase;

class TypicalAgeRangeDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param TypicalAgeRangeDeleted $typicalAgeRangeDeleted
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        TypicalAgeRangeDeleted $typicalAgeRangeDeleted
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalAgeRangeDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param TypicalAgeRangeDeleted $expectedTypicalAgeRangeDeleted
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        TypicalAgeRangeDeleted $expectedTypicalAgeRangeDeleted
    ) {
        $this->assertEquals(
            $expectedTypicalAgeRangeDeleted,
            TypicalAgeRangeDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
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
