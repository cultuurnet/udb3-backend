<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class AddPermissionTest extends TestCase
{
    protected UUID $uuid;

    protected Permission $permission;

    protected AddPermission $addPermission;

    protected function setUp(): void
    {
        $this->uuid = new UUID('dcad2d03-e927-45a9-82d2-4d4703d77a71');

        $this->permission = Permission::aanbodBewerken();

        $this->addPermission = new AddPermission(
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
            $this->addPermission,
            AbstractPermissionCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualCreate = unserialize(serialize($this->addPermission));

        $this->assertEquals($this->addPermission, $actualCreate);
    }
}
