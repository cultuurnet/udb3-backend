<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\EventType;
use PHPUnit\Framework\TestCase;

class TypeUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable()
    {
        $event = new TypeUpdated(
            '89491DC9-9C33-4145-ABB4-AEB33FD93CB6',
            new EventType('0.17.0.0.0', 'Route')
        );

        $eventData = $event->serialize();
        $deserializedEvent = TypeUpdated::deserialize($eventData);

        $this->assertEquals($event, $deserializedEvent);
    }
}
