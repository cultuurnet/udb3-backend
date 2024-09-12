<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use PHPUnit\Framework\TestCase;

class EventEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
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
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
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
