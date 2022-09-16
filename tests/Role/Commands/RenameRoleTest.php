<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class RenameRoleTest extends TestCase
{
    /**
     * @var UUID
     */
    protected $uuid;

    /**
     * @var StringLiteral
     */
    protected $name;

    /**
     * @var RenameRole
     */
    protected $renameRole;

    protected function setUp()
    {
        $this->uuid = new UUID('45264080-03ba-4ae7-87ee-0865a1ed0ae2');

        $this->name = new StringLiteral('newRoleName');

        $this->renameRole = new RenameRole(
            $this->uuid,
            $this->name->toNative()
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->renameRole,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->renameRole->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->renameRole->getName());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualCreate = unserialize(serialize($this->renameRole));

        $this->assertEquals($this->renameRole, $actualCreate);
    }
}
