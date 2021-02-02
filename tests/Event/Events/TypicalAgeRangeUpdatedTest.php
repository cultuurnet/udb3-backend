<?php

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\AgeRange;
use PHPUnit\Framework\TestCase;
use ValueObjects\Person\Age;

class TypicalAgeRangeUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param TypicalAgeRangeUpdated $typicalAgeRangeUpdated
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        TypicalAgeRangeUpdated $typicalAgeRangeUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalAgeRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param TypicalAgeRangeUpdated $expectedTypicalAgeRangeUpdated
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        TypicalAgeRangeUpdated $expectedTypicalAgeRangeUpdated
    ) {
        $this->assertEquals(
            $expectedTypicalAgeRangeUpdated,
            TypicalAgeRangeUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
    {
        return [
            'typical age range' => [
                [
                    'item_id' => 'foo',
                    'typicalAgeRange' => '3-12',
                ],
                new TypicalAgeRangeUpdated(
                    'foo',
                    new AgeRange(new Age(3), new Age(12))
                ),
            ],
        ];
    }
}
