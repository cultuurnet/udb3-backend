<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventOrganizerRelationServiceTest extends TestCase
{
    /**
     * @var EventEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $relationRepository;

    /**
     * @var EventOrganizerRelationService
     */
    private $organizerRelationService;

    public function setUp()
    {
        $this->editService = $this->createMock(EventEditingServiceInterface::class);
        $this->relationRepository = $this->createMock(RepositoryInterface::class);

        $this->organizerRelationService = new EventOrganizerRelationService(
            $this->editService,
            $this->relationRepository
        );
    }

    /**
     * @test
     */
    public function it_removes_the_organizer_from_all_events()
    {
        $organizerId = 'organizer-1';
        $eventIds = ['event-1', 'event-2'];

        $this->relationRepository->expects($this->once())
            ->method('getEventsOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($eventIds);

        $this->editService->expects($this->exactly(2))
            ->method('deleteOrganizer')
            ->withConsecutive(
                [
                    $eventIds[0],
                    $organizerId,
                ],
                [
                    $eventIds[1],
                    $organizerId,
                ]
            );

        $this->organizerRelationService->deleteOrganizer($organizerId);
    }
}
