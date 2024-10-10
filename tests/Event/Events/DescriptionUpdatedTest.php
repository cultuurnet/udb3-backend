<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use PHPUnit\Framework\TestCase;

class DescriptionUpdatedTest extends TestCase
{
    public function serializationDataProvider(): array
    {
        return [
            [
                [
                    'item_id' => 'event-123',
                    'description' => 'description-456',
                ],
                new DescriptionUpdated(
                    'event-123',
                    new Description('description-456')
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        DescriptionUpdated $descriptionUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $descriptionUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        DescriptionUpdated $expectedDescriptionUpdated
    ): void {
        $this->assertEquals(
            $expectedDescriptionUpdated,
            DescriptionUpdated::deserialize($serializedValue)
        );
    }
}
