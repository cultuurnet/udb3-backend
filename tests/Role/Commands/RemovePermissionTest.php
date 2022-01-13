<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class RemovePermissionTest extends TestCase
{
    protected UUID $uuid;

    protected Permission $permission;

    protected RemovePermission $removePermission;

    protected function setUp(): void
    {
        $this->uuid = new UUID();

        $this->permission = Permission::aanbodBewerken();

        $this->removePermission = new RemovePermission(
            $this->uuid,
            $this->permission
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->removePermission,
            AbstractPermissionCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualCreate = unserialize(serialize($this->removePermission));

        $this->assertEquals($this->removePermission, $actualCreate);
    }
}
