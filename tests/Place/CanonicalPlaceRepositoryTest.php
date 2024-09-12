<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalPlaceRepositoryTest extends TestCase
{
    private CanonicalPlaceRepository $canonicalPlaceRepository;

    /**
     * @var PlaceRepository&MockObject
     */
    private $placeRepository;

    protected function setUp(): void
    {
        $this->placeRepository = $this->createMock(PlaceRepository::class);
        $this->canonicalPlaceRepository = new CanonicalPlaceRepository($this->placeRepository);
    }

    /**
     * @test
     */
    public function it_will_return_place_without_defined_canonical(): void
    {
        $placeId = 'ab4a570b-31bc-4538-9129-caf056c716d6';
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
        $placeId = '58eb15e6-31b7-4d83-a637-5b75de21a7b4';
        $place = $this->createMock(Place::class);
        $place->method('getAggregateRootId')->willReturn($placeId);

        $secondLevelDuplicatePlaceId = '42cac5ac-f673-4c3a-9d36-b988f768ab47';
        $secondLevelDuplicatePlace = $this->createMock(Place::class);
        $secondLevelDuplicatePlace->method('getAggregateRootId')->willReturn($secondLevelDuplicatePlaceId);

        $canonicalPlaceId = '29b2352d-1ebb-4ac3-9214-6bba43e6c7b4';
        $canonicalPlace = $this->createMock(Place::class);
        $canonicalPlace->method('getAggregateRootId')->willReturn($canonicalPlaceId);

        $place->method('getCanonicalPlaceId')->willReturn($secondLevelDuplicatePlaceId);
        $secondLevelDuplicatePlace->method('getCanonicalPlaceId')->willReturn($canonicalPlaceId);
        $canonicalPlace->method('getCanonicalPlaceId')->willReturn(null);

        $this->placeRepository->expects($this->any())
            ->method('load')
            ->willReturnMap(
                [
                    [$placeId, $place],
                    [$secondLevelDuplicatePlaceId, $secondLevelDuplicatePlace],
                    [$canonicalPlaceId, $canonicalPlace],
                ]
            );

        $canonicalPlace = $this->canonicalPlaceRepository->findCanonicalFor($placeId);

        $this->assertEquals($canonicalPlaceId, $canonicalPlace->getAggregateRootId());
    }
}
