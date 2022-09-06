<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventOrganizerRelationServiceTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    /**
     * @var EventRelationsRepository|MockObject
     */
    private $relationRepository;

    private EventOrganizerRelationService $organizerRelationService;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->relationRepository = $this->createMock(EventRelationsRepository::class);

        $this->organizerRelationService = new EventOrganizerRelationService(
            $this->commandBus,
            $this->relationRepository
        );
    }

    /**
     * @test
     */
    public function it_removes_the_organizer_from_all_events(): void
    {
        $organizerId = 'organizer-1';
        $eventIds = ['event-1', 'event-2'];

        $this->relationRepository->expects($this->once())
            ->method('getEventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($eventIds);

        $this->organizerRelationService->deleteOrganizer($organizerId);

        $this->assertEquals(
            [
                new DeleteOrganizer($eventIds[0], $organizerId),
                new DeleteOrganizer($eventIds[1], $organizerId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
