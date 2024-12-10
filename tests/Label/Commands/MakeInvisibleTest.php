<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use PHPUnit\Framework\TestCase;

class MakeInvisibleTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_the_labels_beheren_permission(): void
    {
        $command = new MakeInvisible(new Uuid('c08bfa48-9140-43c0-96f6-eced670ffc16'));
        $this->assertInstanceOf(AuthorizableCommand::class, $command);
        $this->assertEquals(Permission::labelsBeheren(), $command->getPermission());
    }
}
