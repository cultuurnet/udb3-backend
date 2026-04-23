<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use PHPUnit\Framework\TestCase;

final class BirthYearRangeUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        BirthYearRangeUpdated $birthYearRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $birthYearRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        BirthYearRangeUpdated $expectedBirthYearRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedBirthYearRangeUpdated,
            BirthYearRangeUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'birth year range' => [
                [
                    'item_id' => 'foo',
                    'birthYearRange' => '2014-2020',
                ],
                new BirthYearRangeUpdated(
                    'foo',
                    new BirthYearRange(2014, 2020)
                ),
            ],
        ];
    }
}
