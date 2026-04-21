<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use PHPUnit\Framework\TestCase;

class TypicalBirthYearRangeUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        TypicalBirthYearRangeUpdated $typicalBirthYearRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalBirthYearRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        TypicalBirthYearRangeUpdated $expectedTypicalBirthYearRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedTypicalBirthYearRangeUpdated,
            TypicalBirthYearRangeUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'typical birth year range' => [
                [
                    'item_id' => 'foo',
                    'typicalBirthYearRange' => '2014-2020',
                ],
                new TypicalBirthYearRangeUpdated(
                    'foo',
                    new BirthYearRange(2014, 2020)
                ),
            ],
        ];
    }
}
