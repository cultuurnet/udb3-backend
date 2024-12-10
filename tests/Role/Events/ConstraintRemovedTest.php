<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class ConstraintRemovedTest extends TestCase
{
    private Uuid $uuid;

    private ConstraintRemoved $event;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('d95f6672-0cb2-4e66-b2e5-4fffd3398eb5');

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
            'uuid' => $this->uuid->toString(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $data = [
            'uuid' => $this->uuid->toString(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
