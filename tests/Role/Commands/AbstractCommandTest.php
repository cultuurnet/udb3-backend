<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractCommandTest extends TestCase
{
    private UUID $uuid;

    /**
     * @var AbstractCommand&MockObject
     */
    private $abstractCommand;

    protected function setUp(): void
    {
        $this->uuid = new UUID('4a1f0f11-8350-4629-ae72-6c20c1145097');

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
            Permission::gebruikersBeheren(),
            $this->abstractCommand->getPermission()
        );
    }
}
