<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLabelEventTest extends TestCase
{
    /**
     * @var AbstractLabelEvent|MockObject
     */
    protected $labelEvent;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var Label
     */
    protected $label;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->label = new Label('LabelTest');
        $this->labelEvent = new LabelAdded($this->itemId, $this->label);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties()
    {
        $expectedItemId = 'Foo';
        $expectedLabel = new Label('LabelTest');
        $expectedLabelEvent = new LabelAdded($expectedItemId, $expectedLabel);

        $this->assertEquals($expectedLabelEvent, $this->labelEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedItemId = 'Foo';
        $expectedLabel = new Label('LabelTest');

        $itemId = $this->labelEvent->getItemId();
        $label = $this->labelEvent->getLabel();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedLabel, $label);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        LabelAdded $abstractLabelEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $abstractLabelEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        LabelAdded $expectedAbstractLabelEvent
    ) {
        $this->assertEquals(
            $expectedAbstractLabelEvent,
            LabelAdded::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractLabelEvent' => [
                [
                    'item_id' => 'madId',
                    'label' => 'label123',
                    'visibility' => true,
                ],
                new LabelAdded(
                    'madId',
                    new Label('label123')
                ),
            ],
            'abstractLabelEvent2' => [
                [
                    'item_id' => 'madId',
                    'label' => 'label123',
                    'visibility' => false,
                ],
                new LabelAdded(
                    'madId',
                    new Label('label123', false)
                ),
            ],
        ];
    }
}
