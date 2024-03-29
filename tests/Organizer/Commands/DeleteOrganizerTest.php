<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class DeleteOrganizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_the_permission_to_manage_organizers(): void
    {
        $command = new DeleteOrganizer('C95FB255-B1F3-4F3F-A48A-E9B845310732');
        $expectedPermission = Permission::organisatiesBeheren();

        $this->assertEquals($expectedPermission, $command->getPermission());
    }
}
