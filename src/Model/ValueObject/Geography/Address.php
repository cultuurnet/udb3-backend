<?php

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

class Address
{
    /**
     * @var Street
     */
    private $street;

    /**
     * @var PostalCode
     */
    private $postalCode;

    /**
     * @var Locality
     */
    private $locality;

    /**
     * @var CountryCode
     */
    private $countryCode;


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

    /**
     * @return Street
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return Address
     */
    public function withStreet(Street $street)
    {
        $c = clone $this;
        $c->street = $street;
        return $c;
    }

    /**
     * @return PostalCode
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @return Address
     */
    public function withPostalCode(PostalCode $postalCode)
    {
        $c = clone $this;
        $c->postalCode = $postalCode;
        return $c;
    }

    /**
     * @return Locality
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @return Address
     */
    public function withLocality(Locality $locality)
    {
        $c = clone $this;
        $c->locality = $locality;
        return $c;
    }

    /**
     * @return CountryCode
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @return Address
     */
    public function withCountryCode(CountryCode $countryCode)
    {
        $c = clone $this;
        $c->countryCode = $countryCode;
        return $c;
    }
}
