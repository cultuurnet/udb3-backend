<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AddPermissionTest extends TestCase
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
     * @var AddPermission
     */
    protected $addPermission;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->permission = Permission::AANBOD_BEWERKEN();

        $this->addPermission = new AddPermission(
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
            $this->addPermission,
            AbstractPermissionCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualCreate = unserialize(serialize($this->addPermission));

        $this->assertEquals($this->addPermission, $actualCreate);
    }
}
