<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class PlaceReference
{
    private ?UUID $placeId;
    private ?TranslatedAddress $address;

    private function __construct(?UUID $placeId, ?TranslatedAddress $address)
    {
        $this->placeId = $placeId;
        $this->address = $address;
    }

    public function getPlaceId(): ?UUID
    {
        return $this->placeId;
    }

    public function getAddress(): ?TranslatedAddress
    {
        return $this->address;
    }

    public static function createWithPlaceId(UUID $placeId): PlaceReference
    {
        return new self($placeId, null);
    }

    public static function createWithAddress(TranslatedAddress $address): PlaceReference
    {
        return new self(null, $address);
    }
}
