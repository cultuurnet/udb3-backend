<?php

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
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
     * @var GeocodingServiceInterface|MockObject
     */
    private $decoratee;

    /**
     * @var CachedGeocodingService
     */
    private $service;

    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->decoratee = $this->createMock(GeocodingServiceInterface::class);
        $this->service = new CachedGeocodingService($this->decoratee, $this->cache);
    }

    /**
     * @test
     */
    public function it_returns_cached_coordinates_if_possible()
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
    public function it_also_caches_when_no_coordinates_were_found()
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
