<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalPlaceServiceTest extends TestCase
{
    /**
     * @var DocumentRepository&MockObject
     */
    private $documentRepository;

    /**
     * @var Repository&MockObject
     */
    private $placeRepository;

    /**
     * @var IriGeneratorInterface&MockObject
     */
    private $iriGenerator;

    /**
     * @var PlaceRelationsRepository&MockObject
     */
    private $placeRelationsRepository;

    private LocalPlaceService $localPlaceService;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createMock(
            DocumentRepository::class
        );

        $this->placeRepository = $this->createMock(Repository::class);

        $this->placeRelationsRepository = $this->createMock(
            PlaceRelationsRepository::class
        );

        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);

        $this->localPlaceService = new LocalPlaceService(
            $this->documentRepository,
            $this->placeRepository,
            $this->placeRelationsRepository,
            $this->iriGenerator
        );
    }

    /**
     * @test
     */
    public function it_returns_places_organized_by_organizer(): void
    {
        $expectedPlaces = ['placeId1', 'placeId2'];

        $this->placeRelationsRepository->expects($this->once())
            ->method('getPlacesOrganizedByOrganizer')
            ->with('organizerId')
            ->willReturn($expectedPlaces);

        $places = $this->localPlaceService->placesOrganizedByOrganizer('organizerId');

        $this->assertEquals($expectedPlaces, $places);
    }
}
