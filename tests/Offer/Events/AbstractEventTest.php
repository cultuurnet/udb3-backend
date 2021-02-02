<?php

namespace CultuurNet\UDB3\Offer\Events;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractEventTest extends TestCase
{
    /**
     * @var AbstractEvent|MockObject
     */
    protected $event;

    /**
     * @var string
     */
    protected $itemId;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->event = new MockAbstractEvent($this->itemId);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties()
    {
        $expectedItemId = 'Foo';
        $expectedEvent = new MockAbstractEvent($expectedItemId);

        $this->assertEquals($expectedEvent, $this->event);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedItemId = 'Foo';

        $itemId = $this->event->getItemId();

        $this->assertEquals($expectedItemId, $itemId);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        MockAbstractEvent $abstractEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $abstractEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        MockAbstractEvent $expectedAbstractEvent
    ) {
        $this->assertEquals(
            $expectedAbstractEvent,
            MockAbstractEvent::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractEvent' => [
                [
                    'item_id' => 'madId',
                ],
                new MockAbstractEvent(
                    'madId'
                ),
            ],
        ];
    }
}
