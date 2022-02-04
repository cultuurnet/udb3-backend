<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var AbstractEvent|MockObject
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID('87b3452e-0b52-4802-a7f4-430ff3640536');

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
            'uuid' => $this->uuid->toString(),
            'name' => $this->name->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }
}
