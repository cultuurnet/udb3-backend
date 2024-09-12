<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use PHPUnit\Framework\TestCase;

class AbstractEventTest extends TestCase
{
    protected MockAbstractEvent $event;

    protected string $itemId;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->event = new MockAbstractEvent($this->itemId);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedEvent = new MockAbstractEvent($expectedItemId);

        $this->assertEquals($expectedEvent, $this->event);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
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
    ): void {
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
    ): void {
        $this->assertEquals(
            $expectedAbstractEvent,
            MockAbstractEvent::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
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
