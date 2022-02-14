<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\JsonLdSerializableInterface;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3ModelAddress;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode as LegacyCountryCode;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Geography\Address instead where possible.
 */
final class Address implements Serializable, JsonLdSerializableInterface
{
    /**
     * @var CountryCode
     */
    private $countryCode;

    /**
     * @var Locality
     */
    private $locality;

    /**
     * @var PostalCode
     */
    private $postalCode;

    /**
     * @var Street
     */
    private $streetAddress;

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
          'streetAddress' => $this->streetAddress->toNative(),
          'postalCode' => $this->postalCode->toNative(),
          'addressLocality' => $this->locality->toNative(),
          'addressCountry' => $this->countryCode,
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
            'addressCountry' => $this->countryCode,
            'addressLocality' => $this->locality->toNative(),
            'postalCode' => $this->postalCode->toNative(),
            'streetAddress' => $this->streetAddress->toNative(),
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
}
