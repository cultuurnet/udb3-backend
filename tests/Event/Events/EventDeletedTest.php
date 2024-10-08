<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use PHPUnit\Framework\TestCase;

class EventDeletedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        EventDeleted $eventDeleted
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $eventDeleted->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        EventDeleted $expectedEventDeleted
    ): void {
        $this->assertEquals(
            $expectedEventDeleted,
            EventDeleted::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_id(): void
    {
        $domainEvent = new EventDeleted('testmefoo');
        $expectedEventId = 'testmefoo';
        $this->assertEquals($expectedEventId, $domainEvent->getItemId());
    }

    public function serializationDataProvider(): array
    {
        return [
            'eventDeleted' => [
                [
                    'item_id' => 'foo',
                ],
                new EventDeleted(
                    'foo'
                ),
            ],
        ];
    }
}
