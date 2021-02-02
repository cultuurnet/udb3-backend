<?php

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class ConstraintRemovedTest extends TestCase
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
     * @var ConstraintRemoved
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID();
        $this->sapiVersion = SapiVersion::V2();

        $this->event = new ConstraintRemoved(
            $this->uuid,
            $this->sapiVersion
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_sapi_version()
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->sapiVersion, $this->event->getSapiVersion());
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
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
