<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class AddressTranslated extends AddressUpdated
{
    private string $languageCode;

    public function __construct(
        string $organizerId,
        string $streetAddress,
        string $postalCode,
        string $locality,
        string $countryCode,
        string $languageCode
    ) {
        parent::__construct($organizerId, $streetAddress, $postalCode, $locality, $countryCode);
        $this->languageCode = $languageCode;
    }

    public function getLanguage(): string
    {
        return $this->languageCode;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'language' => $this->languageCode,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['organizer_id'],
            $data['address']['streetAddress'],
            $data['address']['postalCode'],
            $data['address']['addressLocality'],
            $data['address']['addressCountry'],
            $data['language']
        );
    }
}
