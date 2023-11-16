<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Dto;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Provider\GoogleMaps\Model\GoogleAddress;

class EnrichedAddress implements \JsonSerializable
{
    private string $placeId;
    private string $formattedAddress;
    private string $locationType;
    private array $resultType;
    private bool $partialMatch;
    private Coordinates $coordinates;

    public function __construct(
        string $placeId,
        string $formattedAddress,
        string $locationType,
        array $resultType,
        bool $partialMatch,
        Coordinates $coordinates
    ) {
        $this->placeId = $placeId;
        $this->formattedAddress = $formattedAddress;
        $this->locationType = $locationType;
        $this->resultType = $resultType;
        $this->partialMatch = $partialMatch;
        $this->coordinates = $coordinates;
    }

    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    public function getFormattedAddress(): string
    {
        return $this->formattedAddress;
    }

    public function getLocationType(): string
    {
        return $this->locationType;
    }

    public function getResultType(): array
    {
        return $this->resultType;
    }

    public function getCoordinates(): Coordinates
    {
        return $this->coordinates;
    }

    public function isPartialMatch(): bool
    {
        return $this->partialMatch;
    }

    public function sameAs(self $comparedTo): bool
    {
        return ($this->jsonSerialize() === $comparedTo->jsonSerialize());
    }

    public static function constructFromGoogleAddress(GoogleAddress $googleAddress): self
    {
        $coordinates = $googleAddress->getCoordinates();
        if ($coordinates === null) {
            throw new CollectionIsEmpty('Coordinates from address are empty');
        }

        return new self(
            $googleAddress->getId(),
            $googleAddress->getFormattedAddress(),
            $googleAddress->getLocationType(),
            $googleAddress->getResultType(),
            $googleAddress->isPartialMatch(),
            new Coordinates(
                new Latitude($coordinates->getLatitude()),
                new Longitude($coordinates->getLongitude())
            )
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'placeId' => $this->placeId,
            'formattedAddress' => $this->formattedAddress,
            'locationType' => $this->locationType,
            'resultType' => $this->resultType,
            'partialMatch' => $this->partialMatch,
            'coordinates' => [
                'lat' => $this->coordinates->getLatitude()->toFloat(),
                'long' => $this->coordinates->getLongitude()->toFloat(),
            ],
        ];
    }
}
