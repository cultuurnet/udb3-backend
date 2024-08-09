<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractPermissionCommandTest extends TestCase
{
    private UUID $uuid;

    private Permission $rolePermission;

    /**
     * @var AbstractPermissionCommand&MockObject
     */
    private $abstractPermissionCommand;

    protected function setUp(): void
    {
        $this->uuid = new UUID('e6f81e9d-33c4-4886-a4b5-dba566d811d5');
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
