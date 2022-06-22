<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

abstract class AbstractExtendsTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    protected LabelName $name;

    /**
     * @var AbstractEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID('c69f924a-fdea-487d-a938-183adbe2d594');

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
                'uuid' => $this->uuid->toString(),
                'name' => $this->name->toString(),
            ]
        );

        $this->assertEquals($this->event, $actualEvent);
    }

    /**
     * @return AbstractEvent
     */
    abstract public function createEvent(UUID $uuid, LabelName $name);

    /**
     * @return AbstractEvent
     */
    abstract public function deserialize(array $array);
}
