<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class PlaceReference
{
    /**
     * @var UUID
     */
    private $placeId;

    /**
     * @var Place|null
     */
    private $embeddedPlace;


    private function __construct(UUID $placeId, Place $embeddedPlace = null)
    {
        if ($embeddedPlace) {
            $placeId = $embeddedPlace->getId();
        }

        $this->placeId = $placeId;
        $this->embeddedPlace = $embeddedPlace;
    }

    /**
     * @return UUID
     */
    public function getPlaceId()
    {
        return $this->placeId;
    }

    /**
     * @return Place|null
     */
    public function getEmbeddedPlace()
    {
        return $this->embeddedPlace;
    }

    /**
     * @return PlaceReference
     */
    public static function createWithPlaceId(UUID $placeId)
    {
        return new self($placeId);
    }

    /**
     * @return PlaceReference
     */
    public static function createWithEmbeddedPlace(Place $place)
    {
        return new self($place->getId(), $place);
    }
}
