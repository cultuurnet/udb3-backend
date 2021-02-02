<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Description;
use PHPUnit\Framework\TestCase;

class DescriptionUpdatedTest extends TestCase
{
    public function serializationDataProvider()
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
     * @param array $expectedSerializedValue
     * @param DescriptionUpdated $descriptionUpdated
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        DescriptionUpdated $descriptionUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $descriptionUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param DescriptionUpdated $expectedDescriptionUpdated
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        DescriptionUpdated $expectedDescriptionUpdated
    ) {
        $this->assertEquals(
            $expectedDescriptionUpdated,
            DescriptionUpdated::deserialize($serializedValue)
        );
    }
}
