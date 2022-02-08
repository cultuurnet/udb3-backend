<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class PermissionRemovedTest extends TestCase
{
    protected UUID $uuid;

    protected Permission $permission;

    protected PermissionRemoved $event;

    protected function setUp(): void
    {
        $this->uuid = new UUID('7efe1b01-5a99-48e7-b94a-cd473400563c');

        $this->permission = Permission::aanbodBewerken();

        $this->event = new PermissionRemoved(
            $this->uuid,
            $this->permission
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid_and_a_permission(): void
    {
        $this->assertEquals($this->uuid, $this->event->getUuid());
        $this->assertEquals($this->permission, $this->event->getPermission());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualArray = $this->event->serialize();

        $expectedArray = [
            'uuid' => $this->uuid->toString(),
            'permission' => $this->permission->toString(),
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function it_can_deserialize(): void
    {
        $data = [
            'uuid' => $this->uuid->toString(),
            'permission' => $this->permission->toString(),
        ];
        $actualEvent = $this->event->deserialize($data);
        $expectedEvent = $this->event;

        $this->assertEquals($actualEvent, $expectedEvent);
    }
}
