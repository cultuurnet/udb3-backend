<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class PermissionAddedTest extends TestCase
{
    protected Uuid $uuid;

    protected Permission $permission;

    protected PermissionAdded $event;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('abb75a3f-92b3-4dbf-ba9a-d7e98e4f3655');

        $this->permission = Permission::aanbodBewerken();

        $this->event = new PermissionAdded(
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
