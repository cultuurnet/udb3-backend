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

    protected function setUp(): void
    {
        parent::setUp();

        $this->geocodingServiceMock = $this->createMock(GeocodingCacheFacade::class);
        $this->cacheMock = $this->createMock(Cache::class);

        $this->enrichedCachedGeocodingService = new EnrichedCachedGeocodingService(
            $this->geocodingServiceMock,
            $this->cacheMock,
            true
        );
    }

    public function testAddressEnrichmentFlagOff(): void
    {
        $enrichedCachedGeocodingService = new EnrichedCachedGeocodingService(
            $this->createMock(GeocodingCacheFacade::class),
            $this->cacheMock,
            false
        );

        $this->assertNull(
            $enrichedCachedGeocodingService->saveEnrichedAddress(
                self::ADDRESS,
                self::LOCATION_NAME
            )
        );
    }


    public function testSaveEnrichedAddressNoCacheHit(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('contains')
            ->with(self::ADDRESS . self::LOCATION_NAME)
            ->willReturn(false);

        $enrichedAddress = new EnrichedAddress(
            'place123',
            self::ADDRESS,
            'ROOFTOP',
            ['street_address'],
            true,
            new Coordinates(
                new Latitude(1.07845),
                new Longitude(2.76412)
            )
        );
        $this->geocodingServiceMock->expects($this->once())
            ->method('fetchEnrichedAddress')
            ->with(self::ADDRESS, self::LOCATION_NAME)
            ->willReturn($enrichedAddress);

        $this->assertSame(
            $enrichedAddress,
            $this->enrichedCachedGeocodingService->saveEnrichedAddress(
                self::ADDRESS,
                self::LOCATION_NAME
            )
        );
    }

    public function testSaveEnrichedAddressCacheHit(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('contains')
            ->with(self::ADDRESS . self::LOCATION_NAME)
            ->willReturn(true);

        $enrichedAddress = new EnrichedAddress(
            'place123',
            self::ADDRESS,
            'ROOFTOP',
            ['street_address'],
            true,
            new Coordinates(
                new Latitude(1.07845),
                new Longitude(2.76412)
            )
        );

        $this->cacheMock->expects($this->once())
            ->method('fetch')
            ->with(self::ADDRESS . self::LOCATION_NAME)
            ->willReturn($enrichedAddress);

        $this->assertSame(
            $enrichedAddress,
            $this->enrichedCachedGeocodingService->saveEnrichedAddress(
                self::ADDRESS,
                self::LOCATION_NAME
            )
        );
    }
}
