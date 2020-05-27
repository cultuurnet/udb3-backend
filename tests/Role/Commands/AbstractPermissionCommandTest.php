<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractPermissionCommandTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var Permission
     */
    private $rolePermission;

    /**
     * @var AbstractPermissionCommand
     */
    private $abstractPermissionCommand;

    protected function setUp()
    {
        $this->uuid = new UUID();
        $this->rolePermission = Permission::AANBOD_BEWERKEN();

        $this->abstractPermissionCommand = $this->getMockForAbstractClass(
            AbstractPermissionCommand::class,
            [$this->uuid, $this->rolePermission]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission()
    {
        $this->assertEquals(
            $this->rolePermission,
            $this->abstractPermissionCommand->getRolePermission()
        );
    }
}
