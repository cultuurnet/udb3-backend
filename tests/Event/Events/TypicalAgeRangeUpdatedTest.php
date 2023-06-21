<?php

declare(strict_types=1);

namespace test\Event\Events;

use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Offer\AgeRange;
use PHPUnit\Framework\TestCase;

class TypicalAgeRangeUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        TypicalAgeRangeUpdated $typicalAgeRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalAgeRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        TypicalAgeRangeUpdated $expectedTypicalAgeRangeUpdated
    ): void {
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
