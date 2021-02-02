<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\PlaceEvent;

final class AddressTranslated extends PlaceEvent
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @var Language
     */
    private $language;

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
            'address' => $this->address->serialize(),
            'language' => $this->language->getCode(),
        ];
    }

    public static function deserialize(array $data): AddressTranslated
    {
        return new static(
            $data['place_id'],
            Address::deserialize($data['address']),
            new Language($data['language'])
        );
    }
}
