<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CachedGeocodingServiceTest extends TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    /**
     * @var GeocodingService|MockObject
     */
    private $decoratee;

    /**
     * @var CachedGeocodingService
     */
    private $service;

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
}
