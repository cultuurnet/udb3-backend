<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\TestCase;

class ThemeUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_serializable()
    {
        $event = new ThemeUpdated(
            '9B70683A-5ABF-4A21-80CE-D3A1C0C7BCC2',
            new Theme('0.52.0.0.0', 'Circus')
        );

        $eventData = $event->serialize();
        $deserializedEvent = ThemeUpdated::deserialize($eventData);

        $this->assertEquals($event, $deserializedEvent);
    }
}
