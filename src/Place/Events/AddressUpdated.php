<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Place\PlaceEvent;

final class AddressUpdated extends PlaceEvent
{
    private Address $address;

    public function __construct(string $placeId, Address $address)
    {
        parent::__construct($placeId);
        $this->address = $address;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }


    public function serialize(): array
    {
        return parent::serialize() + [
            'address' => (new AddressNormalizer())->normalize($this->address),
        ];
    }

    public static function deserialize(array $data): AddressUpdated
    {
        return new static(
            $data['place_id'],
            (new AddressDenormalizer())->denormalize($data['address'], Address::class)
        );
    }
}
