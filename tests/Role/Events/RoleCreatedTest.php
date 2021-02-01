<?php

namespace CultuurNet\UDB3\Role\Events;

use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class RoleCreatedTest extends TestCase
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
     * @var RoleCreated
     */
    protected $roleCreated;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->name = new StringLiteral('roleName');

        $this->roleCreated = new RoleCreated(
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
            $this->roleCreated,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid()
    {
        $this->assertEquals($this->uuid, $this->roleCreated->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name()
    {
        $this->assertEquals($this->name, $this->roleCreated->getName());
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $created = RoleCreated::deserialize($this->createdAsArray());

        $this->assertEquals($this->roleCreated, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $createdAsArray = $this->roleCreated->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    /**
     * @return array
     */
    protected function createdAsArray()
    {
        return [
            'uuid' => $this->roleCreated->getUuid()->toNative(),
            'name' => $this->roleCreated->getName()->toNative(),
        ];
    }
}
