<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

class Address
{
    private Street $street;

    private PostalCode $postalCode;

    private Locality $locality;

    private CountryCode $countryCode;

    public function __construct(
        Street $street,
        PostalCode $postalCode,
        Locality $locality,
        CountryCode $countryCode
    ) {
        $this->street = $street;
        $this->postalCode = $postalCode;
        $this->locality = $locality;
        $this->countryCode = $countryCode;
    }

    public function getStreet(): Street
    {
        return $this->street;
    }

    public function withStreet(Street $street): Address
    {
        $c = clone $this;
        $c->street = $street;
        return $c;
    }

    public function getPostalCode(): PostalCode
    {
        return $this->postalCode;
    }

    public function withPostalCode(PostalCode $postalCode): Address
    {
        $c = clone $this;
        $c->postalCode = $postalCode;
        return $c;
    }

    public function getLocality(): Locality
    {
        return $this->locality;
    }

    public function withLocality(Locality $locality): Address
    {
        $c = clone $this;
        $c->locality = $locality;
        return $c;
    }

    public function getCountryCode(): CountryCode
    {
        return $this->countryCode;
    }

    public function withCountryCode(CountryCode $countryCode): Address
    {
        $c = clone $this;
        $c->countryCode = $countryCode;
        return $c;
    }
}
