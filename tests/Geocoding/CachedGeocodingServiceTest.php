<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Json;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Cache\Cache;

class CachedGeocodingServiceTest extends TestCase
{
    private ArrayCache $cache;

    /**
     * @var GeocodingService&MockObject
     */
    private $decoratee;

    private CachedGeocodingService $service;

    public function setUp(): void
    {
        $this->cache = new ArrayCache();
        $this->decoratee = $this->createMock(GeocodingService::class);
        $this->service = new CachedGeocodingService($this->decoratee, $this->cache);
    }

    /**
     * @test
     */
    public function it_returns_cached_coordinates_if_possible(): void
    {
        $address = 'Wetstraat 1, 1000 Brussel, BE';

        $expectedCoordinates = new Coordinates(
            new Latitude(1.07845),
            new Longitude(2.76412)
        );

        $this->decoratee->expects($this->once())
            ->method('getCoordinates')
            ->with($address)
            ->willReturn($expectedCoordinates);

        $freshCoordinates = $this->service->getCoordinates($address);
        $cachedCoordinates = $this->service->getCoordinates($address);

        $this->assertEquals($expectedCoordinates, $freshCoordinates);
        $this->assertEquals($expectedCoordinates, $cachedCoordinates);
    }

    /**
     * @test
     */
    public function it_also_caches_when_no_coordinates_were_found(): void
    {
        $address = 'Eikelberg (achter de bibliotheek), 8340 Sijsele (Damme), BE';

        $this->decoratee->expects($this->once())
            ->method('getCoordinates')
            ->with($address)
            ->willReturn(null);

        $freshCoordinates = $this->service->getCoordinates($address);
        $cachedCoordinates = $this->service->getCoordinates($address);

        $this->assertNull($freshCoordinates);
        $this->assertNull($cachedCoordinates);
    }

    /**
     * @test
     */
    public function it_saves_in_the_cache(): void
    {
        $address = 'Wrong address';

        $geocodingService = $this->createMock(GeocodingService::class);
        $geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with($address)
            ->willReturn(null);
        $geocodingService->expects($this->once())
            ->method('searchTerm')
            ->with($address)
            ->willReturn($address);

        $cache = $this->createMock(Cache::class);

        $cache->expects($this->once())
            ->method('save')
            ->with($address, Json::encode(CachedGeocodingService::NO_COORDINATES_FOUND));

        $service = new CachedGeocodingService($geocodingService, $cache);
        $this->assertNull($service->getCoordinates($address));
    }

    public function test_that_it_uses_the_location_name_in_the_key(): void
    {
        $address = ' Teststraat 1, 8340 Sijsele (Damme), BE ';
        $locationName = ' Eikelberg (achter de bibliotheek) ';

        $geocodingService = $this->createMock(GeocodingService::class);
        $geocodingService->expects($this->once())
            ->method('getCoordinates')
            ->with($address, $locationName)
            ->willReturn(null);
        $geocodingService->expects($this->once())
            ->method('searchTerm')
            ->with($address, $locationName)
            ->willReturn(trim($locationName . $address));

        $cache = $this->createMock(Cache::class);

        $cache->expects($this->once())
            ->method('save')
            ->with(trim($locationName . $address), Json::encode(CachedGeocodingService::NO_COORDINATES_FOUND));

        $service = new CachedGeocodingService($geocodingService, $cache);
        $this->assertNull($service->getCoordinates($address, $locationName));
    }
}
