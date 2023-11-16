<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnrichedCachedGeocodingServiceTest extends TestCase
{
    private const ADDRESS = 'Some Street, City, Country';
    private const LOCATION_NAME = 'Some Location';
    /**
     * @var GeocodingService|MockObject
     */
    private $geocodingServiceMock;

    /**
     * @var Cache|MockObject
     */
    private $cacheMock;

    private EnrichedCachedGeocodingService $enrichedCachedGeocodingService;
    private Coordinates $coordinates;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geocodingServiceMock = $this->createMock(GeocodingService::class);
        $this->cacheMock = $this->createMock(Cache::class);

        $this->enrichedCachedGeocodingService = new EnrichedCachedGeocodingService(
            $this->geocodingServiceMock,
            $this->cacheMock
        );

        $this->coordinates = new Coordinates(new Latitude(40.7128), new Longitude(-74.0060));

        $this->geocodingServiceMock->expects($this->once())
            ->method('getCoordinates')
            ->with(self::ADDRESS, self::LOCATION_NAME)
            ->willReturn($this->coordinates);
    }

    public function testGetCoordinatesWithCache(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('contains')
            ->with(self::ADDRESS . self::LOCATION_NAME)
            ->willReturn(true);

        $this->assertSame(
            $this->coordinates,
            $this->enrichedCachedGeocodingService->getCoordinates(self::ADDRESS, self::LOCATION_NAME)
        );
    }

    public function testGetCoordinatesWithoutCache(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('contains')
            ->with(self::ADDRESS . self::LOCATION_NAME)
            ->willReturn(false);

        $this->geocodingServiceMock->expects($this->once())
            ->method('getEnrichedAddress')
            ->with(self::ADDRESS, self::LOCATION_NAME)
            ->willReturn(new EnrichedAddress(
                'place123',
                self::ADDRESS,
                'ROOFTOP',
                ['street_address'],
                true,
                $this->coordinates
            ));

        $this->assertSame(
            $this->coordinates,
            $this->enrichedCachedGeocodingService->getCoordinates(self::ADDRESS, self::LOCATION_NAME)
        );
    }
}
