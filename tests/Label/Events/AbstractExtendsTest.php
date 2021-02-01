<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

abstract class AbstractExtendsTest extends TestCase
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

        $this->event = $this->createEvent($this->uuid, $this->name);
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_event()
    {
        $this->assertTrue(is_subclass_of(
            $this->event,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $actualEvent = $this->deserialize(
            [
                'uuid' => $this->uuid->toNative(),
                'name' => $this->name->toNative(),
            ]
        );

        $this->assertEquals($this->event, $actualEvent);
    }

    /**
     * @param UUID $uuid
     * @param LabelName $name
     * @return AbstractEvent
     */
    abstract public function createEvent(UUID $uuid, LabelName $name);

    /**
     * @param array $array
     * @return AbstractEvent
     */
    abstract public function deserialize(array $array);
}
