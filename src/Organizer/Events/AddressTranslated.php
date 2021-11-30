<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;

final class AddressTranslated extends AddressUpdated
{
    private string $languageCode;

    public function __construct(
        string $organizerId,
        Address $address,
        string $languageCode
    ) {
        parent::__construct($organizerId, $address);
        $this->languageCode = $languageCode;
    }

    public function getLanguage(): Language
    {
        return new Language($this->languageCode);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'language' => $this->languageCode,
        ];
    }

    /**
     * @return AddressTranslated
     */
    public static function deserialize(array $data): AddressUpdated
    {
        return new self(
            $data['organizer_id'],
            Address::deserialize($data['address']),
            $data['language']
        );
    }
}
