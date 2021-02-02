<?php

namespace CultuurNet\UDB3\Place\Events;

use PHPUnit\Framework\TestCase;

final class MarkedAsDuplicateTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable()
    {
        $event = new MarkedAsDuplicate(
            'a9088117-5ec8-4117-8ce0-5ce27e685055',
            '7ee54099-9e0f-4c55-9a28-b548ef2a41ba'
        );

        $eventAsArray = [
            'place_id' => 'a9088117-5ec8-4117-8ce0-5ce27e685055',
            'duplicate_of' => '7ee54099-9e0f-4c55-9a28-b548ef2a41ba',
        ];

        $serializedEvent = $event->serialize();
        $this->assertEquals($eventAsArray, $serializedEvent);

        $deserializedEvent = MarkedAsDuplicate::deserialize($eventAsArray);
        $this->assertEquals($event, $deserializedEvent);
    }
}
