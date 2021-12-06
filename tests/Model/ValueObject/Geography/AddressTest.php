<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    private Street $street;

    private PostalCode $postalCode;

    private Locality $locality;

    private CountryCode $countryCode;

    private Address $address;

    public function setUp(): void
    {
        $this->street = new Street('Henegouwenkaai 41-43');
        $this->postalCode = new PostalCode('1080');
        $this->locality = new Locality('Brussel');
        $this->countryCode = new CountryCode('BE');

        $this->address = new Address(
            $this->street,
            $this->postalCode,
            $this->locality,
            $this->countryCode
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_street(): void
    {
        $updatedStreet = new Street('Henegouwenkaai 41-43 UPDATED');
        $updatedAddress = $this->address->withStreet($updatedStreet);

        $this->assertNotEquals($this->address, $updatedAddress);
        $this->assertEquals($this->street, $this->address->getStreet());
        $this->assertEquals($updatedStreet, $updatedAddress->getStreet());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_postal_code(): void
    {
        $updatedPostalCode = new PostalCode('1080 UPDATED');
        $updatedAddress = $this->address->withPostalCode($updatedPostalCode);

        $this->assertNotEquals($this->address, $updatedAddress);
        $this->assertEquals($this->postalCode, $this->address->getPostalCode());
        $this->assertEquals($updatedPostalCode, $updatedAddress->getPostalCode());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_locality(): void
    {
        $updatedLocality = new Locality('Brussel UPDATED');
        $updatedAddress = $this->address->withLocality($updatedLocality);

        $this->assertNotEquals($this->address, $updatedAddress);
        $this->assertEquals($this->locality, $this->address->getLocality());
        $this->assertEquals($updatedLocality, $updatedAddress->getLocality());
    }

    /**
     * @test
     */
    public function it_should_return_a_copy_with_an_updated_country_code(): void
    {
        $updatedCountry = new CountryCode('NL');
        $updatedAddress = $this->address->withCountryCode($updatedCountry);

        $this->assertNotEquals($this->address, $updatedAddress);

        $this->assertEquals($this->countryCode, $this->address->getCountryCode());
        $this->assertEquals($updatedCountry, $updatedAddress->getCountryCode());

        $this->assertEquals('BE', $this->address->getCountryCode()->getCode());
        $this->assertEquals('NL', $updatedAddress->getCountryCode()->getCode());
    }

    /**
     * @test
     */
    public function it_compares_equality(): void
    {
        $this->assertTrue($this->address->sameAs(
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        ));

        $this->assertFalse($this->address->sameAs(
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('9000'),
                new Locality('Gent'),
                new CountryCode('BE')
            )
        ));
    }
}
