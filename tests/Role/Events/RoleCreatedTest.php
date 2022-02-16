<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

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
        $this->uuid = new UUID('12c98a43-978b-4a6f-a7da-67a4350a6fa1');

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
            'uuid' => $this->roleCreated->getUuid()->toString(),
            'name' => $this->roleCreated->getName()->toNative(),
        ];
    }
}
