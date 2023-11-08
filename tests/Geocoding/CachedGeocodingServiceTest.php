<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\CacheEncoder\CoordinateEncoder;
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

    /**
     * @var GeocodingService|MockObject
     */
    private $decorator;

    private CachedGeocodingService $service;

    public function setUp(): void
    {
        $cache = new ArrayCache();
        $this->decorator = $this->createMock(GeocodingService::class);
        $this->service = new CachedGeocodingService($this->decorator, $cache, new CoordinateEncoder());
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

        $this->decorator->expects($this->once())
            ->method('fetchAddress')
            ->with($address)
            ->willReturn($expectedCoordinates);

        $freshCoordinates = $this->service->fetchAddress($address);
        $cachedCoordinates = $this->service->fetchAddress($address);

        $this->assertEquals($expectedCoordinates, $freshCoordinates);
        $this->assertEquals($expectedCoordinates, $cachedCoordinates);
    }

    /**
     * @test
     */
    public function it_also_caches_when_no_coordinates_were_found(): void
    {
        $address = 'Eikelberg (achter de bibliotheek), 8340 Sijsele (Damme), BE';

        $this->decorator->expects($this->exactly(2))
            ->method('fetchAddress')
            ->with($address)
            ->willReturn(null);

        $freshCoordinates = $this->service->fetchAddress($address);
        $cachedCoordinates = $this->service->fetchAddress($address);

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
            ->method('fetchAddress')
            ->with($address)
            ->willReturn(null);

        $cache = $this->createMock(Cache::class);

        $cache->expects($this->once())
            ->method('save')
            ->with($address, Json::encode(CachedGeocodingService::NO_COORDINATES_FOUND));

        $service = new CachedGeocodingService($geocodingService, $cache, new CoordinateEncoder());
        $this->assertNull($service->getCoordinates($address));
    }
}
