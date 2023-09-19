<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
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
            'address' => $this->address->serialize(),
        ];
    }

    public static function deserialize(array $data): AddressUpdated
    {
        return new static(
            $data['place_id'],
            new Address(
                new Street($data['address']),
                new PostalCode($data['address']),
                new Locality($data['address']),
                new CountryCode($data['address'])
            )
        );
    }
}
