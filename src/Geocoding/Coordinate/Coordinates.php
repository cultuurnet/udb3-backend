<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Coordinate;

use Geocoder\Location;

class Coordinates
{
    private Latitude $lat;

    private Longitude $long;

    public function __construct(Latitude $lat, Longitude $long)
    {
        $this->lat = $lat;
        $this->long = $long;
    }

    public function getLatitude(): Latitude
    {
        return $this->lat;
    }

    public function getLongitude(): Longitude
    {
        return $this->long;
    }

    public function sameAs(Coordinates $coordinates): bool
    {
        return $coordinates->getLatitude()->sameAs($this->lat) &&
            $coordinates->getLongitude()->sameAs($this->long);
    }

    public static function fromLatLonString(string $latLon): Coordinates
    {
        $split = explode(',', $latLon);

        if (count($split) !== 2) {
            throw new \InvalidArgumentException('Lat lon string is not in the expected format (lat,lon).');
        }

        $lat = new Latitude((float) $split[0]);
        $lon = new Longitude((float) $split[1]);

        return new Coordinates($lat, $lon);
    }

    public static function fromLocation(Location $location): Coordinates
    {
        return new Coordinates(
            new Latitude($location->getCoordinates()->getLatitude()),
            new Longitude($location->getCoordinates()->getLongitude()),
        );
    }
}
