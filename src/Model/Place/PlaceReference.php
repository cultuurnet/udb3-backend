<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class PlaceReference
{
    private ?Uuid $placeId;
    private ?TranslatedAddress $address;

    private function __construct(?Uuid $placeId, ?TranslatedAddress $address)
    {
        $this->placeId = $placeId;
        $this->address = $address;
    }

    public function getPlaceId(): ?Uuid
    {
        return $this->placeId;
    }

    public function getAddress(): ?TranslatedAddress
    {
        return $this->address;
    }

    public static function createWithPlaceId(Uuid $placeId): PlaceReference
    {
        return new self($placeId, null);
    }

    public static function createWithAddress(TranslatedAddress $address): PlaceReference
    {
        return new self(null, $address);
    }
}
