<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

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

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
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
            $data['coordinates']['lat'],
            $data['coordinates']['long']
        );
    }
}
