<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class BirthYearRangeDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        BirthYearRangeDeleted $birthYearRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $birthYearRangeDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        BirthYearRangeDeleted $expectedBirthYearRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedBirthYearRangeDeleted,
            BirthYearRangeDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'birth year range' => [
                [
                    'item_id' => 'foo',
                ],
                new BirthYearRangeDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
