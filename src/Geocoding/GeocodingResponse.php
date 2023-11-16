<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Geocoder\Location;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;

class GeocodingResponse
{
    private Location $location;

    /**
     * @throws Exception
     */
    public function __construct(
        Geocoder $geocoder,
        string $address,
        string $locationName=null
    ) {
        $response = $geocoder->geocode($locationName ? $address . ' ' . $locationName : $address);

        $this->location = $response->first();
    }

    public function getCoordinates(): Coordinates
    {
        $coordinates = $this->location->getCoordinates();

        if ($coordinates === null) {
            throw new CollectionIsEmpty('Coordinates from address are empty');
        }

        return new Coordinates(
            new Latitude($coordinates->getLatitude()),
            new Longitude($coordinates->getLongitude())
        );
    }

    public function getEnrichedAddress(): EnrichedAddress
    {
        if (! $this->location instanceof GoogleAddress) {
            throw new NoGoogleAddressToEnrichReceived();
        }

        return EnrichedAddress::constructFromGoogleAddress($this->location);
    }
}
