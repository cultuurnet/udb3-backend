<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractConstraintEventTest extends TestCase
{
    private UUID $uuid;

    private Query $query;

    /**
     * @var AbstractConstraintEvent|MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID();

        $this->query = new Query('category_flandersregion_name:"Regio Aalst"');

        $this->event = $this->getMockForAbstractClass(
            AbstractConstraintEvent::class,
            [$this->uuid, $this->query]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_query(): void
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->query, $this->event->getQuery());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            'uuid' => $this->uuid->toNative(),
            'query' => $this->query->toNative(),
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
            'query' => $this->query->toNative(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
