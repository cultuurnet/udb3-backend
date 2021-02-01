<?php

namespace CultuurNet\UDB3\Actor;

use PHPUnit\Framework\TestCase;

class ActorEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param MockActorEvent $actorEvent
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
        MockActorEvent $actorEvent
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $actorEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $serializedValue
     * @param MockActorEvent $expectedActorEvent
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
        MockActorEvent $expectedActorEvent
    ) {
        $this->assertEquals(
            $expectedActorEvent,
            MockActorEvent::deserialize($serializedValue)
        );
    }

    public function it_can_return_its_properties()
    {
        $expectedId = 'actor_id';
        $mockActorEvent = new MockActorEvent('actor_id');

        $this->assertEquals($expectedId, $mockActorEvent->getActorId());
    }

    public function serializationDataProvider()
    {
        return [
            'mockActorEvent' => [
                [
                    'actor_id' => 'actor_id',
                ],
                new MockActorEvent(
                    'actor_id'
                ),
            ],
        ];
    }
}
