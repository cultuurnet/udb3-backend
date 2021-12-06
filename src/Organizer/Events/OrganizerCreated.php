<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;

final class OrganizerCreated extends OrganizerEvent
{
    public string $title;

    private ?string $streetAddress;

    private ?string $postalCode;

    private ?string $locality;

    private ?string $countryCode;

    /**
     * @var string[]
     */
    public array $phones;

    /**
     * @var string[]
     */
    public array $emails;

    /**
     * @var string[]
     */
    public array $urls;

    public function __construct(
        string $id,
        string $title,
        ?string $streetAddress,
        ?string $postalCode,
        ?string $locality,
        ?string $countryCode,
        array $phones,
        array $emails,
        array $urls
    ) {
        parent::__construct($id);

        $this->title = $title;
        $this->streetAddress = $streetAddress;
        $this->postalCode = $postalCode;
        $this->locality = $locality;
        $this->countryCode = $countryCode;
        $this->phones = $phones;
        $this->emails = $emails;
        $this->urls = $urls;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function hasAddress(): bool
    {
        return isset($this->streetAddress, $this->locality, $this->postalCode, $this->countryCode);
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        $addresses = [];
        if ($this->hasAddress()) {
            $addresses[] = new Address(
                new Street($this->streetAddress),
                new PostalCode($this->postalCode),
                new Locality($this->locality),
                new Country(CountryCode::fromNative($this->countryCode))
            );
        }
        return $addresses;
    }

    /**
     * @return string[]
     */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /**
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @return string[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    public function serialize(): array
    {
        $addresses = [];
        if ($this->hasAddress()) {
            $addresses[] = [
                'streetAddress' => $this->streetAddress,
                'postalCode' => $this->postalCode,
                'addressLocality' => $this->locality,
                'addressCountry' => $this->countryCode,
            ];
        }

        return parent::serialize() + [
            'title' => $this->title,
            'addresses' => $addresses,
            'phones' => $this->getPhones(),
            'emails' => $this->getEmails(),
            'urls' => $this->getUrls(),
        ];
    }

    public static function deserialize(array $data): OrganizerCreated
    {
        return new static(
            $data['organizer_id'],
            $data['title'],
            $data['addresses'][0]['streetAddress'] ?? null,
            $data['addresses'][0]['postalCode'] ?? null,
            $data['addresses'][0]['addressLocality'] ?? null,
            $data['addresses'][0]['addressCountry'] ?? null,
            $data['phones'],
            $data['emails'],
            $data['urls'],
        );
    }
}
