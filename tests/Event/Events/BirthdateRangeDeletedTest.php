<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

final class BirthdateRangeDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        BirthdateRangeDeleted $birthdateRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $birthdateRangeDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        BirthdateRangeDeleted $expectedBirthdateRangeDeleted
    ): void {
        $this->assertEquals(
            $expectedBirthdateRangeDeleted,
            BirthdateRangeDeleted::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'birthdate range' => [
                [
                    'item_id' => 'foo',
                ],
                new BirthdateRangeDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
