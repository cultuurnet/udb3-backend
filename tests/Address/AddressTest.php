<?php

namespace CultuurNet\UDB3\Address;

use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;

class AddressTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_compare_two_addresses()
    {
        $addressLeuven = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $addressBrussel = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            Country::fromNative('BE')
        );

        $this->assertTrue($addressLeuven->sameAs(clone $addressLeuven));
        $this->assertFalse($addressLeuven->sameAs($addressBrussel));
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_address()
    {
        $udb3ModelAddress = new \CultuurNet\UDB3\Model\ValueObject\Geography\Address(
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Street('Henegouwenkaai 41-43'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode('1080'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Locality('Brussel'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode('BE')
        );

        $expected = new Address(
            new Street('Henegouwenkaai 41-43'),
            new PostalCode('1080'),
            new Locality('Brussel'),
            new Country(CountryCode::fromNative('BE'))
        );

        $actual = Address::fromUdb3ModelAddress($udb3ModelAddress);

        $this->assertEquals($expected, $actual);
    }
}
