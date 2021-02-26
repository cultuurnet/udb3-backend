<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\Serializer\Serializable;

abstract class PlaceEvent implements Serializable
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
        return [
            'place_id' => $this->placeId,
        ];
    }
}
