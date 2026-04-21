<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class TypicalBirthYearRangeDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        TypicalBirthYearRangeDeleted $typicalBirthYearRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalBirthYearRangeDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        TypicalBirthYearRangeDeleted $expectedTypicalBirthYearRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedTypicalBirthYearRangeDeleted,
            TypicalBirthYearRangeDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'typical birth year range' => [
                [
                    'item_id' => 'foo',
                ],
                new TypicalBirthYearRangeDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
