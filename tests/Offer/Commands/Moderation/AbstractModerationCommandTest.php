<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class AbstractModerationCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_permission_aanbod_modereren()
    {
        /** @var AbstractModerationCommand $abstractModerationCommand */
        $abstractModerationCommand = $this->getMockForAbstractClass(
            AbstractModerationCommand::class,
            ['e1d026e2-d158-40e9-b82a-dfcd62de2a77']
        );

        $this->assertEquals(
            Permission::AANBOD_MODEREREN(),
            $abstractModerationCommand->getPermission()
        );
    }
}
