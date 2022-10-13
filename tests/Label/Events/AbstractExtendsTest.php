<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

abstract class AbstractExtendsTest extends TestCase
{
    protected UUID $uuid;

    protected string $name;

    protected AbstractEvent $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID('c69f924a-fdea-487d-a938-183adbe2d594');

        $this->name = '2dotstwice';

        $this->event = $this->createEvent($this->uuid, $this->name);
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $actualEvent = $this->deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name,
            ]
        );

        $this->assertEquals($this->event, $actualEvent);
    }

    abstract public function createEvent(UUID $uuid, string $name): AbstractEvent;

    abstract public function deserialize(array $array): AbstractEvent;
}
