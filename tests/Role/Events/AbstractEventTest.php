<?php

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractEventTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var AbstractEvent
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID();

        $this->event = $this->getMockForAbstractClass(
            AbstractEvent::class,
            [$this->uuid]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualArray = $this->event->serialize();

        $expectedArray = ['uuid' => $this->uuid->toNative()];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
