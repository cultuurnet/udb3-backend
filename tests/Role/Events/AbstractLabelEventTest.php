<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLabelEventTest extends TestCase
{
    protected Uuid $labelId;

    protected Uuid $uuid;

    /**
     * @var AbstractLabelEvent&MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('1e99c6ab-6ff2-4611-96fa-eda8b8a78ae9');
        $this->labelId = new Uuid('d50852d1-5351-46bc-8221-238c2d47e3cf');

        $this->event = $this->getMockForAbstractClass(
            AbstractLabelEvent::class,
            [$this->uuid, $this->labelId]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_label_id(): void
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->labelId, $this->event->getLabelId());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            AbstractEvent::UUID => $this->uuid->toString(),
            AbstractLabelEvent::LABEL_ID => $this->labelId->toString(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $data = [
             AbstractEvent::UUID => $this->uuid->toString(),
            AbstractLabelEvent::LABEL_ID => $this->labelId->toString(),
        ];

        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
