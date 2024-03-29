<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

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

    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getLocality(): string
    {
        return $this->locality;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'address' => [
                'streetAddress' => $this->streetAddress,
                'postalCode' => $this->postalCode,
                'addressLocality' => $this->locality,
                'addressCountry' => $this->countryCode,
            ],
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['organizer_id'],
            $data['address']['streetAddress'],
            $data['address']['postalCode'],
            $data['address']['addressLocality'],
            $data['address']['addressCountry']
        );
    }
}
