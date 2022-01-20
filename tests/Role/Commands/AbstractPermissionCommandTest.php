<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractPermissionCommandTest extends TestCase
{
    private UUID $uuid;

    private Permission $rolePermission;

    /**
     * @var AbstractPermissionCommand|MockObject
     */
    private $abstractPermissionCommand;

    protected function setUp(): void
    {
        $this->uuid = new UUID();
        $this->rolePermission = Permission::aanbodBewerken();

        $this->abstractPermissionCommand = $this->getMockForAbstractClass(
            AbstractPermissionCommand::class,
            [$this->uuid, $this->rolePermission]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_permission(): void
    {
        $this->assertEquals(
            $this->rolePermission,
            $this->abstractPermissionCommand->getRolePermission()
        );
    }
}
