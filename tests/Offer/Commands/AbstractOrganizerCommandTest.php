<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractOrganizerCommandTest extends TestCase
{
    /**
     * @var AbstractOrganizerCommand&MockObject
     */
    protected $organizerCommand;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $organizerId;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->organizerId = 'organizer-456';

        $this->organizerCommand = $this->getMockForAbstractClass(
            AbstractOrganizerCommand::class,
            [$this->itemId, $this->organizerId]
        );
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $organizerId = $this->organizerCommand->getOrganizerId();
        $expectedOrganizerId = 'organizer-456';

        $this->assertEquals($expectedOrganizerId, $organizerId);

        $itemId = $this->organizerCommand->getItemId();
        $expectedItemId = 'Foo';

        $this->assertEquals($expectedItemId, $itemId);
    }
}
