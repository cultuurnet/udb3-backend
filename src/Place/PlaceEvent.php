<?php

namespace CultuurNet\UDB3\Place;

use Broadway\Serializer\SerializableInterface;

abstract class PlaceEvent implements SerializableInterface
{
    /**
     * @var string
     */
    protected $placeId;

    public function __construct(string $placeId)
    {
        $this->placeId = $placeId;
    }

    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    public function serialize(): array
    {
        return array(
            'place_id' => $this->placeId,
        );
    }
}
