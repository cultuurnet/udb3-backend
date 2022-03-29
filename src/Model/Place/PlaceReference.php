<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class PlaceReference
{
    private UUID $placeId;

    private function __construct(UUID $placeId)
    {
        $this->placeId = $placeId;
    }

    public function getPlaceId(): UUID
    {
        return $this->placeId;
    }

    public static function createWithPlaceId(UUID $placeId): PlaceReference
    {
        return new self($placeId);
    }
}
