<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Dto;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates as CoordinatesGeocoder;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;
use PHPUnit\Framework\TestCase;

class EnrichedAddressTest extends TestCase
{
    private const PLACE_ID = 'place123';
    private const ADDRESS = 'Some Street, City, Country';
    private const LOCATION_TYPE = 'ROOFTOP';
    private const RESULT_TYPE = ['street_address'];
    private const LATITUDE = 40.7128;
    private const LONGITUDE = -74.0060;

    public function testJsonSerialize(): void
    {
        $enrichedAddress = new EnrichedAddress(
            self::PLACE_ID,
            self::ADDRESS,
            self::LOCATION_TYPE,
            self::RESULT_TYPE,
            true,
            new Coordinates(new Latitude(self::LATITUDE), new Longitude(self::LONGITUDE))
        );

        $expectedJson = json_encode([
            'placeId' => self::PLACE_ID,
            'formattedAddress' => self::ADDRESS,
            'locationType' => self::LOCATION_TYPE,
            'resultType' => self::RESULT_TYPE,
            'partialMatch' => true,
            'coordinates' => [
                'lat' => self::LATITUDE,
                'long' => self::LONGITUDE,
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertEquals($expectedJson, json_encode($enrichedAddress, JSON_THROW_ON_ERROR));
    }

    public function testConstructFromGoogleAddress(): void
    {
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

        $enrichedAddress = EnrichedAddress::constructFromGoogleAddress($googleAddress);

        $this->assertEquals(self::PLACE_ID, $enrichedAddress->getPlaceId());
        $this->assertEquals(self::ADDRESS, $enrichedAddress->getFormattedAddress());
        $this->assertEquals(self::LOCATION_TYPE, $enrichedAddress->getLocationType());
        $this->assertEquals(self::RESULT_TYPE, $enrichedAddress->getResultType());
        $this->assertTrue($enrichedAddress->isPartialMatch());
        $this->assertEquals(self::LATITUDE, $enrichedAddress->getCoordinates()->getLatitude()->toFloat());
        $this->assertEquals(self::LONGITUDE, $enrichedAddress->getCoordinates()->getLongitude()->toFloat());
    }

    public function testSameAs(): void
    {
        $enrichedAddress = new EnrichedAddress(
            self::PLACE_ID,
            self::ADDRESS,
            self::LOCATION_TYPE,
            self::RESULT_TYPE,
            true,
            new Coordinates(new Latitude(self::LATITUDE), new Longitude(self::LONGITUDE))
        );

        $differentAddress = new EnrichedAddress(
            self::PLACE_ID,
            self::ADDRESS,
            self::LOCATION_TYPE,
            self::RESULT_TYPE,
            false,
            new Coordinates(new Latitude(self::LATITUDE), new Longitude(self::LONGITUDE))
        );

        $this->assertTrue($enrichedAddress->sameAs(clone $enrichedAddress));
        $this->assertFalse($enrichedAddress->sameAs($differentAddress));
    }
}
