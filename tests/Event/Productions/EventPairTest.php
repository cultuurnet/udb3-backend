<?php

namespace CultuurNet\UDB3\Event\Productions;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;

class EventPairTest extends TestCase
{
    /**
     * @test
     */
    public function itCanSerializeToArray(): void
    {
        $eventOne = Uuid::uuid4()->toString();
        $eventTwo = Uuid::uuid4()->toString();
        $eventPair = new EventPair($eventOne, $eventTwo);

        $eventAsArray = $eventPair->asArray();

        $deserializedEventPair = EventPair::fromArray($eventAsArray);

        $this->assertEquals($eventPair, $deserializedEventPair);
    }
}
