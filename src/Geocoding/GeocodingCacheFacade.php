<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use CultuurNet\UDB3\Geocoding\Exception\NoCoordinatesForAddressReceived;
use CultuurNet\UDB3\Geocoding\Exception\NoGoogleAddressReceived;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Geocoder\Location;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;

/**
 * This class provides a cache wrapper for the duration for the duration of a single request
 * This in case both fetchCoordinates and fetchEnrichedAddress is called
 */
class GeocodingCacheFacade
{
    private Geocoder $geocoder;

    /** @var Location[] */
    private array $cache = [];

    public function __construct(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
    }

    public function fetchCoordinates(string $address, string $locationName = null): Coordinates
    {
        $location = $this->fetchLocation($address, $locationName);

        if (!$location instanceof Location) {
            throw new NoGoogleAddressReceived();
        }

        $coordinates = $location->getCoordinates();

        if ($coordinates === null) {
            throw new NoCoordinatesForAddressReceived();
        }

        return new Coordinates(
            new Latitude($coordinates->getLatitude()),
            new Longitude($coordinates->getLongitude())
        );
    }

    public function fetchEnrichedAddress(string $address, string $locationName = null): EnrichedAddress
    {
        $location = $this->fetchLocation($address, $locationName);

        if (!$location instanceof GoogleAddress) {
            throw new NoGoogleAddressReceived();
        }

        return EnrichedAddress::constructFromGoogleAddress($location);
    }

    protected function fetchLocation(string $address, string $locationName = null): ?Location
    {
        $key = $address . $locationName;

        if (!isset($this->cache[$key])) {
            try {
                $this->cache[$key] = $this->geocoder->geocode($locationName ? $address . ' ' . $locationName : $address)->first();
            } catch (Exception $e) {
                $this->cache[$key] = null;
            }
        }

        return $this->cache[$key];
    }
}
