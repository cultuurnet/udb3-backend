<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class AbstractUpdateOrganizerCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var AbstractUpdateOrganizerCommand
     */
    private $updateOrganizerCommand;

    protected function setUp()
    {
        $this->organizerId = 'f4490009-6207-4868-87a3-e1f96934e055';

        $this->updateOrganizerCommand = $this->getMockForAbstractClass(
            AbstractUpdateOrganizerCommand::class,
            [
                $this->organizerId,
            ]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateOrganizerCommand->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_returns_organizer_id_as_item_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->updateOrganizerCommand->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_has_permission_organisaties_bewerken()
    {
        $this->assertEquals(
            Permission::ORGANISATIES_BEWERKEN(),
            $this->updateOrganizerCommand->getPermission()
        );
    }
}
