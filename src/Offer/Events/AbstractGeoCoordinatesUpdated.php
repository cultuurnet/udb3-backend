<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;

abstract class AbstractGeoCoordinatesUpdated extends AbstractEvent
{
    private Coordinates $coordinates;

    final public function __construct(string $itemId, Coordinates $coordinates)
    {
        parent::__construct($itemId);
        $this->coordinates = $coordinates;
    }

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'coordinates' => [
                'lat' => $this->coordinates->getLatitude()->toFloat(),
                'long' => $this->coordinates->getLongitude()->toFloat(),
            ],
        ];
    }

    public static function deserialize(array $data): AbstractGeoCoordinatesUpdated
    {
        return new static(
            $data['item_id'],
            new Coordinates(
                new Latitude($data['coordinates']['lat']),
                new Longitude($data['coordinates']['long'])
            )
        );
    }
}
