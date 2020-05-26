<?php

namespace CultuurNet\UDB3\Role\Commands;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class CreateRoleTest extends TestCase
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
     * @var CreateRole
     */
    protected $createRole;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->name = new StringLiteral('roleName');

        $this->createRole = new CreateRole(
            $this->uuid,
            $this->name
        );
    }

    /**
     * @test
     */
    public function it_extends_an_abstract_command()
    {
        $this->assertTrue(is_subclass_of(
            $this->createRole,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->createRole->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->createRole->getName());
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $actualCreate = unserialize(serialize($this->createRole));

        $this->assertEquals($this->createRole, $actualCreate);
    }
}
