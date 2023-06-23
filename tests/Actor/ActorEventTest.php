<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Actor;

use PHPUnit\Framework\TestCase;

class ActorEventTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        MockActorEvent $actorEvent
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $actorEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        MockActorEvent $expectedActorEvent
    ): void {
        $this->assertEquals(
            $expectedActorEvent,
            MockActorEvent::deserialize($serializedValue)
        );
    }

    public function it_can_return_its_properties(): void
    {
        $expectedId = 'actor_id';
        $mockActorEvent = new MockActorEvent('actor_id');

        $this->assertEquals($expectedId, $mockActorEvent->getActorId());
    }

    public function serializationDataProvider(): array
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
