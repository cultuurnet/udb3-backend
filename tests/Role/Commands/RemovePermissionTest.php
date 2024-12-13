<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class RemovePermissionTest extends TestCase
{
    protected Uuid $uuid;

    protected Permission $permission;

    protected RemovePermission $removePermission;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('c43b7c6e-ff4b-4222-9a57-41adc4d27625');

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
