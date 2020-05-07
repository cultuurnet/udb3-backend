<?php

namespace CultuurNet\UDB3\Event\Commands;

use PHPUnit\Framework\TestCase;

class DeleteEventTest extends TestCase
{
    /**
     * @var DeleteEvent
     */
    protected $deleteEvent;

    public function setUp()
    {
        $this->deleteEvent = new DeleteEvent(
            'id'
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values()
    {
        $expectedId = 'id';

        $this->assertEquals($expectedId, $this->deleteEvent->getItemId());
    }
}
