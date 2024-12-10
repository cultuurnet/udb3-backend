<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class RenameRoleTest extends TestCase
{
    protected UUID $uuid;

    protected string $name;

    protected RenameRole $renameRole;

    protected function setUp(): void
    {
        $this->uuid = new UUID('45264080-03ba-4ae7-87ee-0865a1ed0ae2');

        $this->name = 'newRoleName';

        $this->renameRole = new RenameRole(
            $this->uuid,
            $this->name
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->renameRole,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->renameRole->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->renameRole->getName());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualCreate = unserialize(serialize($this->renameRole));

        $this->assertEquals($this->renameRole, $actualCreate);
    }
}
