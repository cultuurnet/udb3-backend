<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_compare_two_addresses(): void
    {
        $addressLeuven = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $addressBrussel = new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->assertTrue($addressLeuven->sameAs(clone $addressLeuven));
        $this->assertFalse($addressLeuven->sameAs($addressBrussel));
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_address(): void
    {
        $udb3ModelAddress = new \CultuurNet\UDB3\Model\ValueObject\Geography\Address(
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Street('Henegouwenkaai 41-43'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode('1080'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Locality('Brussel'),
            new CountryCode('BE')
        );

        $expected = new Address(
            new Street('Henegouwenkaai 41-43'),
            new PostalCode('1080'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $actual = Address::fromUdb3ModelAddress($udb3ModelAddress);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_convert_to_an_udb3_model_address(): void
    {
        $originalAddress = new Address(
            new Street('Henegouwenkaai 41-43'),
            new PostalCode('1080'),
            new Locality('Brussel'),
            new CountryCode('BE')
        );

        $convertAddress = $originalAddress->toUdb3ModelAddress();

        $udb3ModelAddress = new \CultuurNet\UDB3\Model\ValueObject\Geography\Address(
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Street('Henegouwenkaai 41-43'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode('1080'),
            new \CultuurNet\UDB3\Model\ValueObject\Geography\Locality('Brussel'),
            new CountryCode('BE')
        );

        $this->assertEquals($udb3ModelAddress->getStreet()->toString(), $convertAddress->getStreet()->toString());
        $this->assertEquals($udb3ModelAddress->getPostalCode()->toString(), $convertAddress->getPostalCode()->toString());
        $this->assertEquals($udb3ModelAddress->getLocality()->toString(), $convertAddress->getLocality()->toString());
    }
}
