<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

class RoleCreatedTest extends TestCase
{
    protected Uuid $uuid;

    protected string $name;

    protected RoleCreated $roleCreated;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('12c98a43-978b-4a6f-a7da-67a4350a6fa1');

        $this->name = 'roleName';

        $this->roleCreated = new RoleCreated(
            $this->uuid,
            $this->name
        );
    }

    /**
     * @test
     */
    public function it_extends_an_event(): void
    {
        $this->assertTrue(is_subclass_of(
            $this->roleCreated,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->roleCreated->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->roleCreated->getName());
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $created = RoleCreated::deserialize($this->createdAsArray());

        $this->assertEquals($this->roleCreated, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $createdAsArray = $this->roleCreated->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    protected function createdAsArray(): array
    {
        return [
            'uuid' => $this->roleCreated->getUuid()->toString(),
            'name' => $this->roleCreated->getName(),
        ];
    }
}
