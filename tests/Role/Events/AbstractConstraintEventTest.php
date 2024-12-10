<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractConstraintEventTest extends TestCase
{
    private Uuid $uuid;

    private Query $query;

    /**
     * @var AbstractConstraintEvent&MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('8e846b43-3e65-4672-a76b-cdf30ab4f9de');

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
            'uuid' => $this->uuid->toString(),
            'query' => $this->query->toString(),
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
            'query' => $this->query->toString(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
