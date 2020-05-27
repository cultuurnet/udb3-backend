<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class RemovePermissionTest extends TestCase
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
     * @var RemovePermission
     */
    protected $removePermission;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->permission = Permission::AANBOD_BEWERKEN();

        $this->removePermission = new AddPermission(
            $this->uuid,
            $this->permission
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->removePermission,
            AbstractPermissionCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualCreate = unserialize(serialize($this->removePermission));

        $this->assertEquals($this->removePermission, $actualCreate);
    }
}
