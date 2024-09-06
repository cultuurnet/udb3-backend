<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GeocodingServiceFactoryTest extends TestCase
{
    public function testCreateServiceWithEnrichedFeature(): void
    {
        $factory = new GeocodingServiceFactory();

        $service = $factory->createService(
            $this->createMock(LoggerInterface::class),
            'google_api_key'
        );

        $this->assertInstanceOf(GeocodingServiceWithLocationName::class, $service);
    }

    public function testGetCacheNameWithEnrichedFeature(): void
    {
        $factory = new GeocodingServiceFactory();

        $this->assertEquals('geocoords_with_location_name', $factory->getCacheName());
    }
}
