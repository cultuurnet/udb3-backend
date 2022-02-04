<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class RoleRenamedTest extends TestCase
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
     * @var RoleRenamed
     */
    protected $roleRenamed;

    protected function setUp()
    {
        $this->uuid = new UUID('e97ed374-ca50-4d07-a893-637729341ab3');

        $this->name = new StringLiteral('roleName');

        $this->roleRenamed = new RoleRenamed(
            $this->uuid,
            $this->name
        );
    }

    /**
     * @test
     */
    public function it_extends_an_event()
    {
        $this->assertTrue(is_subclass_of(
            $this->roleRenamed,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->roleRenamed->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->roleRenamed->getName());
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $created = RoleRenamed::deserialize($this->createdAsArray());

        $this->assertEquals($this->roleRenamed, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $createdAsArray = $this->roleRenamed->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    /**
     * @return array
     */
    protected function createdAsArray()
    {
        return [
            'uuid' => $this->roleRenamed->getUuid()->toString(),
            'name' => $this->roleRenamed->getName()->toNative(),
        ];
    }
}
