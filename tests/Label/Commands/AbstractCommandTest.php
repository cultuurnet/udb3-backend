<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AbstractCommandTest extends TestCase
{
    private UUID $uuid;

    /**
     * @var AbstractCommand|MockObject
     */
    private $abstractCommand;

    protected function setUp(): void
    {
        $this->uuid = new UUID();

        $this->abstractCommand = $this->getMockForAbstractClass(
            AbstractCommand::class,
            [$this->uuid]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_uuid(): void
    {
        $this->assertEquals($this->uuid, $this->abstractCommand->getUuid());
    }

    /**
     * @test
     */
    public function it_has_an_item_id(): void
    {
        $this->assertEquals(
            $this->uuid->toNative(),
            $this->abstractCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_has_permission_aanbod_labelen(): void
    {
        $this->assertEquals(
            Permission::labelsBeheren(),
            $this->abstractCommand->getPermission()
        );
    }
}
