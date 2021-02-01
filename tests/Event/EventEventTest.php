<?php

namespace CultuurNet\UDB3\Event;

use PHPUnit\Framework\TestCase;

class EventEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param MockEventEvent $mockEventEvent
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        MockEventEvent $mockEventEvent
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $mockEventEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param MockEventEvent $expectedMockEventEvent
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        MockEventEvent $expectedMockEventEvent
    ): void {
        $this->assertEquals(
            $expectedMockEventEvent,
            MockEventEvent::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_id(): void
    {
        $eventEvent = new MockEventEvent('testmefoo');
        $expectedEventEventId = 'testmefoo';
        $this->assertEquals($expectedEventEventId, $eventEvent->getEventId());
    }

    public function serializationDataProvider(): array
    {
        return [
            'mockEventEvent' => [
                [
                    'event_id' => 'foo',
                ],
                new MockEventEvent(
                    'foo'
                ),
            ],
        ];
    }
}
