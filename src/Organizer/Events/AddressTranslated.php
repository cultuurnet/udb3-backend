<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;

final class AddressTranslated extends AddressUpdated
{
    /**
     * @var Language
     */
    private $language;

    public function __construct(
        string $organizerId,
        Address $address,
        Language $language
    ) {
        parent::__construct($organizerId, $address);
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'language' => $this->getLanguage()->getCode(),
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
            new Language($data['language'])
        );
    }
}
