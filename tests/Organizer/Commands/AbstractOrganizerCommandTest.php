<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractOrganizerCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var AbstractOrganizerCommand|MockObject
     */
    private $command;

    public function setUp()
    {
        $this->organizerId = '123';
        $this->command = $this->getMockForAbstractClass(AbstractOrganizerCommand::class, [$this->organizerId]);
    }

    /**
     * @test
     */
    public function it_returns_the_organizer_id()
    {
        $this->assertEquals($this->organizerId, $this->command->getOrganizerId());
    }
}
