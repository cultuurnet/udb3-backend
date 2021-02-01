<?php

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractLabelEventTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $labelId;

    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var AbstractLabelEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID();
        $this->labelId = new UUID();

        $this->event = $this->getMockForAbstractClass(
            AbstractLabelEvent::class,
            [$this->uuid, $this->labelId]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_label_id()
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->labelId, $this->event->getLabelId());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            AbstractEvent::UUID => $this->uuid->toNative(),
            AbstractLabelEvent::LABEL_ID => $this->labelId->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $data = [
             AbstractEvent::UUID => $this->uuid->toNative(),
            AbstractLabelEvent::LABEL_ID => $this->labelId->toNative(),
        ];

        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
