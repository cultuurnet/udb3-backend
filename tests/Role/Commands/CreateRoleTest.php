<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class CreateRoleTest extends TestCase
{
    protected Uuid $uuid;

    protected string $name;

    protected CreateRole $createRole;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('da3d5569-9d0b-4f52-b61e-1f81b4deeb01');

        $this->name = 'roleName';

        $this->createRole = new CreateRole(
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
            $this->createRole,
            AbstractCommand::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->createRole->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->createRole->getName());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $actualCreate = unserialize(serialize($this->createRole));

        $this->assertEquals($this->createRole, $actualCreate);
    }
}
