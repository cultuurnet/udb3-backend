<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;

class AddressUpdated extends OrganizerEvent
{
    private string $countryCode;

    private string $locality;

    private string $postalCode;

    private string $streetAddress;

    public function __construct(
        string $organizerId,
        string $streetAddress,
        string $postalCode,
        string $locality,
        string $countryCode
    ) {
        parent::__construct($organizerId);
        $this->streetAddress = $streetAddress;
        $this->postalCode = $postalCode;
        $this->locality = $locality;
        $this->countryCode = $countryCode;
    }

    public function getAddress(): Address
    {
        return new Address(
            new Street($this->streetAddress),
            new PostalCode($this->postalCode),
            new Locality($this->locality),
            new Country(
                CountryCode::fromNative($this->countryCode)
            )
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'streetAddress' => $this->streetAddress,
            'postalCode' => $this->postalCode,
            'addressLocality' => $this->locality,
            'addressCountry' => $this->countryCode,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['organizer_id'],
            $data['streetAddress'],
            $data['postalCode'],
            $data['addressLocality'],
            $data['addressCountry']
        );
    }
}
