<?php

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class PermissionAddedTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var Permission
     */
    protected $permission;

    /**
     * @var PermissionAdded
     */
    protected $event;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->permission = Permission::AANBOD_BEWERKEN();

        $this->event = new PermissionAdded(
            $this->uuid,
            $this->permission
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_permission()
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->permission, $this->event->getPermission());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            'uuid' => $this->uuid->toNative(),
            'permission' => $this->permission->toNative(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function it_can_deserialize()
    {
        $data = [
            'uuid' => $this->uuid->toNative(),
            'permission' => $this->permission->toNative(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
