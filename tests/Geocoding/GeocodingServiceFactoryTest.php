<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeocodingServiceFactoryTest extends TestCase
{
    public function testCreateServiceWithEnrichedFeature(): void
    {
        $factory = new GeocodingServiceFactory(true);

        $service = $factory->createService(
            $this->createMock(LoggerInterface::class),
            'google_api_key'
        );

        $this->assertInstanceOf(GeocodingServiceWithLocationName::class, $service);
    }

    public function testCreateServiceWithBasicFeature(): void
    {
        $factory = new GeocodingServiceFactory(false);

        $service = $factory->createService(
            $this->createMock(LoggerInterface::class),
            'google_api_key'
        );

        $this->assertInstanceOf(DefaultGeocodingService::class, $service);
    }

    public function testGetCacheNameWithEnrichedFeature(): void
    {
        $factory = new GeocodingServiceFactory(true);

        $this->assertEquals('geocoords_with_location_name', $factory->getCacheName());
    }

    public function testGetCacheNameWithBasicFeature(): void
    {
        $factory = new GeocodingServiceFactory(false);

        $this->assertEquals('geocoords', $factory->getCacheName());
    }
}
