<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Exception\NoCoordinatesForAddressReceived;
use CultuurNet\UDB3\Geocoding\Exception\NoGoogleAddressReceived;
use Geocoder\Geocoder;
use Geocoder\Location;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates as CoordinatesGeocoder;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;
use PHPUnit\Framework\TestCase;

class GeocodingCacheFacadeTest extends TestCase
{
    private const LOCATION_NAME = 'My location name';
    private const PLACE_ID = 'place123';
    private const ADDRESS = 'Some Street, City, Country';
    private const LOCATION_TYPE = 'ROOFTOP';
    private const RESULT_TYPE = ['street_address'];
    private const LATITUDE = 40.7128;
    private const LONGITUDE = -74.0060;

    public function testFetchCoordinates(): void
    {
        $geocoderMock = $this->createMock(Geocoder::class);

        $locationMock = $this->createMock(Location::class);
        $locationMock
            ->method('getCoordinates')
            ->willReturn(new CoordinatesGeocoder(self::LATITUDE, self::LONGITUDE));

        $geocoderMock
            ->method('geocode')
            ->willReturn(new AddressCollection([$locationMock]));

        $geocodingCacheFacade = new GeocodingCacheFacade($geocoderMock);

        $coordinates = $geocodingCacheFacade->fetchCoordinates(self::ADDRESS, self::LOCATION_NAME);

        $this->assertEquals(self::LATITUDE, $coordinates->getLatitude()->toFloat());
        $this->assertEquals(self::LONGITUDE, $coordinates->getLongitude()->toFloat());
    }

    public function testFetchEnrichedAddress(): void
    {
        $geocoderMock = $this->createMock(Geocoder::class);

        $googleAddress = new GoogleAddress(
            'phpunit',
            new AdminLevelCollection(),
            new CoordinatesGeocoder(self::LATITUDE, self::LONGITUDE)
        );
        $googleAddress = $googleAddress
            ->withId(self::PLACE_ID)
            ->withFormattedAddress(self::ADDRESS)
            ->withLocationType(self::LOCATION_TYPE)
            ->withResultType(self::RESULT_TYPE)
            ->withPartialMatch(true);

        $geocoderMock
            ->method('geocode')
            ->willReturn(new AddressCollection([$googleAddress]));

        $geocodingCacheFacade = new GeocodingCacheFacade($geocoderMock);

        $enrichedAddress = $geocodingCacheFacade->fetchEnrichedAddress(
            self::ADDRESS,
            self::LOCATION_NAME
        );

        $this->assertEquals(self::PLACE_ID, $enrichedAddress->getPlaceId());
        $this->assertEquals(self::ADDRESS, $enrichedAddress->getFormattedAddress());
        $this->assertEquals(self::LOCATION_TYPE, $enrichedAddress->getLocationType());
        $this->assertEquals(self::RESULT_TYPE, $enrichedAddress->getResultType());
        $this->assertTrue($enrichedAddress->isPartialMatch());
        $this->assertEquals(self::LATITUDE, $enrichedAddress->getCoordinates()->getLatitude()->toFloat());
        $this->assertEquals(self::LONGITUDE, $enrichedAddress->getCoordinates()->getLongitude()->toFloat());
    }

    public function testFetchCoordinatesNoGoogleAddressReceived(): void
    {
        $this->expectException(NoGoogleAddressReceived::class);

        $geocoderMock = $this->createMock(Geocoder::class);

        $geocoderMock
            ->method('geocode')
            ->willReturn(new AddressCollection());

        $geocodingCacheFacade = new GeocodingCacheFacade($geocoderMock);

        $geocodingCacheFacade->fetchCoordinates(self::ADDRESS, self::LOCATION_NAME);
    }

    public function testFetchCoordinatesNoCoordinatesForAddressReceived(): void
    {
        $this->expectException(NoCoordinatesForAddressReceived::class);

        $geocoderMock = $this->createMock(Geocoder::class);

        $locationMock = $this->createMock(Location::class);
        $locationMock
            ->method('getCoordinates')
            ->willReturn(null);

        $geocoderMock
            ->method('geocode')
            ->willReturn(new AddressCollection([$locationMock]));

        $geocodingCacheFacade = new GeocodingCacheFacade($geocoderMock);

        $geocodingCacheFacade->fetchCoordinates(self::ADDRESS, self::LOCATION_NAME);
    }
}
