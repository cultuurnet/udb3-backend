<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\JsonLdSerializableInterface;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3ModelAddress;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Geography\Address instead where possible.
 */
final class Address implements Serializable, JsonLdSerializableInterface
{
    private CountryCode $countryCode;
    private Locality $locality;
    private PostalCode $postalCode;
    private Street $streetAddress;

    public function __construct(
        Street $streetAddress,
        PostalCode $postalCode,
        Locality $locality,
        CountryCode $countryCode
    ) {
        $this->streetAddress = $streetAddress;
        $this->postalCode = $postalCode;
        $this->locality = $locality;
        $this->countryCode = $countryCode;
    }

    public function getCountryCode(): CountryCode
    {
        return $this->countryCode;
    }

    public function getLocality(): Locality
    {
        return $this->locality;
    }

    public function getPostalCode(): PostalCode
    {
        return $this->postalCode;
    }

    public function getStreetAddress(): Street
    {
        return $this->streetAddress;
    }

    public function serialize(): array
    {
        return [
          'streetAddress' => $this->streetAddress->toString(),
          'postalCode' => $this->postalCode->toString(),
          'addressLocality' => $this->locality->toString(),
          'addressCountry' => $this->countryCode->toString(),
        ];
    }

    public static function deserialize(array $data): Address
    {
        return new self(
            new Street($data['streetAddress']),
            new PostalCode($data['postalCode']),
            new Locality($data['addressLocality']),
            new CountryCode($data['addressCountry'])
        );
    }

    public function toJsonLd(): array
    {
        return [
            'addressCountry' => $this->countryCode->toString(),
            'addressLocality' => $this->locality->toString(),
            'postalCode' => $this->postalCode->toString(),
            'streetAddress' => $this->streetAddress->toString(),
        ];
    }

    public function sameAs(Address $otherAddress): bool
    {
        return $this->toJsonLd() === $otherAddress->toJsonLd();
    }

    public static function fromUdb3ModelAddress(Udb3ModelAddress $address): Address
    {
        return new self(
            new Street($address->getStreet()->toString()),
            new PostalCode($address->getPostalCode()->toString()),
            new Locality($address->getLocality()->toString()),
            new CountryCode($address->getCountryCode()->toString())
        );
    }

    public function toUdb3ModelAddress(): Udb3ModelAddress
    {
        return new Udb3ModelAddress(
            new Udb3Street($this->getStreetAddress()->toString()),
            new Udb3PostalCode($this->getPostalCode()->toString()),
            new Udb3Locality($this->getLocality()->toString()),
            new CountryCode($this->getCountryCode()->toString())
        );
    }
}
