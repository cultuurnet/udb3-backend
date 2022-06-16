<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class LabelRemovedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        LabelRemoved $labelRemoved
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $labelRemoved->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        LabelRemoved $expectedLabelRemovedEvent
    ): void {
        $this->assertEquals(
            $expectedLabelRemovedEvent,
            LabelRemoved::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'label removed event' => [
                [
                    'item_id' => 'foo',
                    'label' => 'Label1',
                    'visibility' => true,
                ],
                new LabelRemoved(
                    'foo',
                    'Label1'
                ),
            ],
        ];
    }
}
