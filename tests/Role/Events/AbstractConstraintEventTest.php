<?php

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractConstraintEventTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var SapiVersion
     */
    protected $sapiVersion;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var AbstractConstraintEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->sapiVersion = SapiVersion::V2();

        $this->query = new Query('category_flandersregion_name:"Regio Aalst"');

        $this->event = $this->getMockForAbstractClass(
            AbstractConstraintEvent::class,
            [$this->uuid, $this->sapiVersion, $this->query]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_query()
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->sapiVersion, $this->event->getSapiVersion());
        $this->assertEquals($this->query, $this->event->getQuery());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            'uuid' => $this->uuid->toNative(),
            'sapiVersion' => $this->sapiVersion->toNative(),
            'query' => $this->query->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $data = [
            'uuid' => $this->uuid->toNative(),
            'sapiVersion' => $this->sapiVersion->toNative(),
            'query' => $this->query->toNative(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
