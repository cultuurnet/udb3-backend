<?php

namespace CultuurNet\UDB3\Place;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class CanonicalPlaceRepositoryTest extends TestCase
{
    /**
     * @var CanonicalPlaceRepository
     */
    private $canonicalPlaceRepository;

    /**
     * @var PlaceRepository | MockObject
     */
    private $placeRepository;

    protected function setUp()
    {
        $this->placeRepository = $this->createMock(PlaceRepository::class);
        $this->canonicalPlaceRepository = new CanonicalPlaceRepository($this->placeRepository);
    }

    /**
     * @test
     */
    public function it_will_return_place_without_defined_canonical(): void
    {
        $placeId = UUID::generateAsString();
        $canonicalPlace = $this->createMock(Place::class);
        $canonicalPlace->method('getCanonicalPlaceId')->willReturn(null);
        $canonicalPlace->method('getAggregateRootId')->willReturn($placeId);
        $this->placeRepository->method('load')->with($placeId)->willReturn($canonicalPlace);

        $canonicalPlace = $this->canonicalPlaceRepository->findCanonicalFor($placeId);

        $this->assertEquals($placeId, $canonicalPlace->getAggregateRootId());
    }

    /**
     * @test
     */
    public function it_will_return_canonical_place(): void
    {
        $placeId = UUID::generateAsString();
        $place = $this->createMock(Place::class);
        $place->method('getAggregateRootId')->willReturn($placeId);

        $secondLevelDuplicatePlaceId = Uuid::generateAsString();
        $secondLevelDuplicatePlace = $this->createMock(Place::class);
        $secondLevelDuplicatePlace->method('getAggregateRootId')->willReturn($secondLevelDuplicatePlaceId);

        $canonicalPlaceId = UUID::generateAsString();
        $canonicalPlace = $this->createMock(Place::class);
        $canonicalPlace->method('getAggregateRootId')->willReturn($canonicalPlaceId);

        $place->method('getCanonicalPlaceId')->willReturn($secondLevelDuplicatePlaceId);
        $this->placeRepository->expects($this->at(0))->method('load')->with($placeId)->willReturn($place);

        $secondLevelDuplicatePlace->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $this->placeRepository->expects($this->at(1))->method('load')->with($secondLevelDuplicatePlaceId)->willReturn($secondLevelDuplicatePlace);

        $canonicalPlace->method('getCanonicalPlaceId')->willReturn(null);
        $this->placeRepository->expects($this->at(2))->method('load')->with($canonicalPlaceId)->willReturn($canonicalPlace);

        $canonicalPlace = $this->canonicalPlaceRepository->findCanonicalFor($placeId);

        $this->assertEquals($canonicalPlaceId, $canonicalPlace->getAggregateRootId());
    }
}
