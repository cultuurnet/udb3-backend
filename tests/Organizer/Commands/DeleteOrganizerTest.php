<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class DeleteOrganizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_the_permission_to_manage_organizers()
    {
        $command = new DeleteOrganizer('C95FB255-B1F3-4F3F-A48A-E9B845310732', 'D2723C33-677C-4358-BA3F-FF364BD12088');
        $expectedPermission = Permission::ORGANISATIES_BEHEREN();

        $this->assertEquals($expectedPermission, $command->getPermission());
    }
}
