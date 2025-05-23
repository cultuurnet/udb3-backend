<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractCommandTest extends TestCase
{
    private Uuid $uuid;

    /**
     * @var AbstractCommand&MockObject
     */
    private $abstractCommand;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('3f946ee2-1637-4499-ba2d-4f0fdac69c0f');

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
            $this->uuid->toString(),
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
