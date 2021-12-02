<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;

final class GeoCoordinatesUpdated extends OrganizerEvent
{
    private float $latitude;

    private float $longitude;

    public function __construct(string $organizerId, float $latitude, float $longitude)
    {
        parent::__construct($organizerId);
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function coordinates(): Coordinates
    {
        return new Coordinates(
            new Latitude($this->latitude),
            new Longitude($this->longitude)
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'coordinates' => [
                'lat' => $this->latitude,
                'long' => $this->longitude,
            ],
        ];
    }

    public static function deserialize(array $data): GeoCoordinatesUpdated
    {
        return new static(
            $data['organizer_id'],
            new Coordinates(
                new Latitude($data['coordinates']['lat']),
                new Longitude($data['coordinates']['long'])
            )
        );
    }
}
