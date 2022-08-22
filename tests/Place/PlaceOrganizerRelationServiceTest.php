<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Offer\OfferEditingServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceOrganizerRelationServiceTest extends TestCase
{
    /**
     * @var OfferEditingServiceInterface|MockObject
     */
    private $editService;

    /**
     * @var PlaceRelationsRepository|MockObject
     */
    private $relationRepository;

    /**
     * @var PlaceOrganizerRelationService
     */
    private $organizerRelationService;

    public function setUp()
    {
        $this->editService = $this->createMock(DefaultOfferEditingService::class);
        $this->relationRepository = $this->createMock(PlaceRelationsRepository::class);

        $this->organizerRelationService = new PlaceOrganizerRelationService(
            $this->editService,
            $this->relationRepository
        );
    }

    /**
     * @test
     */
    public function it_removes_the_organizer_from_all_places()
    {
        $organizerId = 'organizer-1';
        $placeIds = ['place-1', 'place-2'];

        $this->relationRepository->expects($this->once())
            ->method('getPlacesOrganizedByOrganizer')
            ->with($organizerId)
            ->willReturn($placeIds);

        $this->editService->expects($this->exactly(2))
            ->method('deleteOrganizer')
            ->withConsecutive(
                [
                    $placeIds[0],
                    $organizerId,
                ],
                [
                    $placeIds[1],
                    $organizerId,
                ]
            );

        $this->organizerRelationService->deleteOrganizer($organizerId);
    }
}
