<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\PlaceEvent;

final class AddressTranslated extends PlaceEvent
{
    private Address $address;

    private Language $language;

    public function __construct(string $placeId, Address $address, Language $language)
    {
        parent::__construct($placeId);
        $this->address = $address;
        $this->language = $language;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'address' => (new AddressNormalizer())->normalize($this->address),
            'language' => $this->language->getCode(),
        ];
    }

    public static function deserialize(array $data): AddressTranslated
    {
        return new static(
            $data['place_id'],
            (new AddressDenormalizer())->denormalize($data['address'], Address::class),
            new Language($data['language'])
        );
    }
}
