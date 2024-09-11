<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractEventTest extends TestCase
{
    protected UUID $uuid;

    /**
     * @var AbstractEvent&MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID('f3062b50-636b-43cd-917d-fe14f1d0d7ac');

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

        $expectedArray = ['uuid' => $this->uuid->toString()];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
