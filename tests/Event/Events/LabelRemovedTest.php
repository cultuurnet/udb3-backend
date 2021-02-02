<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class LabelRemovedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param LabelRemoved $labelRemoved
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        LabelRemoved $labelRemoved
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $labelRemoved->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param LabelRemoved $expectedLabelRemovedEvent
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        LabelRemoved $expectedLabelRemovedEvent
    ) {
        $this->assertEquals(
            $expectedLabelRemovedEvent,
            LabelRemoved::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider()
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
                    new Label('Label1')
                ),
            ],
        ];
    }
}
