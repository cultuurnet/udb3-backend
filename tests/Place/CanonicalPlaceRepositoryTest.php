<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalPlaceRepositoryTest extends TestCase
{
    private CanonicalPlaceRepository $canonicalPlaceRepository;

    /**
     * @var DuplicatePlaceRepository&MockObject
     */
    private $duplicatePlaceRepository;

    protected function setUp(): void
    {
        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);
        $this->canonicalPlaceRepository = new CanonicalPlaceRepository($this->duplicatePlaceRepository);
    }

    /**
     * @test
     */
    public function it_will_return_null_without_defined_canonical(): void
    {
        $placeId = 'ab4a570b-31bc-4538-9129-caf056c716d6';
        $this->duplicatePlaceRepository
            ->expects($this->once())
            ->method('getCanonicalOfPlace')
            ->with($placeId)
            ->willReturn(null);

        $this->assertNull($this->canonicalPlaceRepository->findCanonicalIdFor($placeId));
    }

    /**
     * @test
     */
    public function it_will_return_a_canonical_place_id(): void
    {
        $placeId = '58eb15e6-31b7-4d83-a637-5b75de21a7b4';

        $canonicalPlaceId = '29b2352d-1ebb-4ac3-9214-6bba43e6c7b4';

        $this->duplicatePlaceRepository
            ->expects($this->once())
            ->method('getCanonicalOfPlace')
            ->with($placeId)
            ->willReturn($canonicalPlaceId);

        $this->assertEquals(
            $canonicalPlaceId,
            $this->canonicalPlaceRepository->findCanonicalIdFor($placeId)
        );
    }
}
