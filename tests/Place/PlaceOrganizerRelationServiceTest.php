<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceOrganizerRelationServiceTest extends TestCase
{
    private TraceableCommandBus $commandBus;

    /**
     * @var PlaceRelationsRepository|MockObject
     */
    private $relationRepository;

    private PlaceOrganizerRelationService $organizerRelationService;

    public function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->relationRepository = $this->createMock(PlaceRelationsRepository::class);

        $this->organizerRelationService = new PlaceOrganizerRelationService(
            $this->commandBus,
            $this->relationRepository
        );
    }

    /**
     * @test
     */
    public function it_removes_the_organizer_from_all_places(): void
    {
        $organizerId = 'organizer-1';
        $placeIds = ['place-1', 'place-2'];

        $this->relationRepository->expects($this->once())
            ->method('getPlacesOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($placeIds);

        $this->organizerRelationService->deleteOrganizer($organizerId);

        $this->assertEquals(
            [
                new DeleteOrganizer($placeIds[0], $organizerId),
                new DeleteOrganizer($placeIds[1], $organizerId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}
