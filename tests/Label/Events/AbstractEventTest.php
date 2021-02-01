<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractEventTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var LabelName
     */
    protected $name;

    /**
     * @var AbstractEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->name = new LabelName('2dotstwice');

        $this->event = $this->getMockForAbstractClass(
            AbstractEvent::class,
            [$this->uuid, $this->name]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            'uuid' => $this->uuid->toNative(),
            'name' => $this->name->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
