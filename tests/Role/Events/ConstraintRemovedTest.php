<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class ConstraintRemovedTest extends TestCase
{
    private UUID $uuid;

    private ConstraintRemoved $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID();

        $this->event = new ConstraintRemoved($this->uuid);
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

        $expectedArray = [
            'uuid' => $this->uuid->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $data = [
            'uuid' => $this->uuid->toNative(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
