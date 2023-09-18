<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class RoleRenamedTest extends TestCase
{
    protected UUID $uuid;

    protected string $name;

    protected RoleRenamed $roleRenamed;

    protected function setUp(): void
    {
        $this->uuid = new UUID('e97ed374-ca50-4d07-a893-637729341ab3');

        $this->name = 'roleName';

        $this->roleRenamed = new RoleRenamed(
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
            $this->roleRenamed,
            AbstractEvent::class
        ));
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->roleRenamed->getUuid());
    }

    /**
     * @test
     */
    public function it_stores_a_name(): void
    {
        $this->assertEquals($this->name, $this->roleRenamed->getName());
    }

    /**
     * @test
     */
    public function it_can_deserialize(): void
    {
        $created = RoleRenamed::deserialize($this->createdAsArray());

        $this->assertEquals($this->roleRenamed, $created);
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $createdAsArray = $this->roleRenamed->serialize();

        $this->assertEquals($this->createdAsArray(), $createdAsArray);
    }

    protected function createdAsArray(): array
    {
        return [
            'uuid' => $this->roleRenamed->getUuid()->toString(),
            'name' => $this->roleRenamed->getName(),
        ];
    }
}
