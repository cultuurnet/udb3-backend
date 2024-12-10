<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractPermissionEventTest extends TestCase
{
    protected Uuid $uuid;

    protected Permission $permission;

    /**
     * @var AbstractPermissionEvent&MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('56a7c6f9-729d-423f-a82b-a85ec4bc2c32');

        $this->permission = Permission::aanbodBewerken();

        $this->event = $this->getMockForAbstractClass(
            AbstractPermissionEvent::class,
            [$this->uuid, $this->permission]
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
