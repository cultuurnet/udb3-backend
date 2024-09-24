<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use PHPUnit\Framework\TestCase;

class CultureFeedAddressFactoryTest extends TestCase
{
    private CultureFeedAddressFactory $factory;

    public function setUp(): void
    {
        $this->factory = new CultureFeedAddressFactory();
    }

    /**
     * @test
     */
    public function it_converts_a_cdb_physical_address_to_an_udb3_address(): void
    {
        $cdbPhysicalAddress = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $cdbPhysicalAddress->setStreet('Jeugdlaan');
        $cdbPhysicalAddress->setHouseNumber(2);
        $cdbPhysicalAddress->setZip('3900');
        $cdbPhysicalAddress->setCity('Overpelt');
        $cdbPhysicalAddress->setCountry('BE');

        $expectedAddress = new Address(
            new Street('Jeugdlaan 2'),
            new PostalCode('3900'),
            new Locality('Overpelt'),
            new CountryCode('BE')
        );

        $actualAddress = $this->factory->fromCdbAddress($cdbPhysicalAddress);

        $this->assertEquals($expectedAddress, $actualAddress);
    }

    /**
     * @test
     * @dataProvider incompletePhysicalAddressDataProvider
     */
    public function it_throws_an_exception_when_a_required_field_is_missing_on_the_physical_address(
        \CultureFeed_Cdb_Data_Address_PhysicalAddress $incompletePhysicalAddress,
        string $exceptionMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->factory->fromCdbAddress($incompletePhysicalAddress);
    }

    public function incompletePhysicalAddressDataProvider(): array
    {
        $withoutStreet = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $withoutStreet->setHouseNumber(2);
        $withoutStreet->setZip('3900');
        $withoutStreet->setCity('Overpelt');
        $withoutStreet->setCountry('BE');

        $withoutZip = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $withoutZip->setStreet('Jeugdlaan');
        $withoutZip->setHouseNumber(2);
        $withoutZip->setCity('Overpelt');
        $withoutZip->setCountry('BE');

        $withoutCity = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $withoutCity->setStreet('Jeugdlaan');
        $withoutCity->setHouseNumber(2);
        $withoutCity->setZip('3900');
        $withoutCity->setCountry('BE');

        $withoutCountry = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $withoutCountry->setStreet('Jeugdlaan');
        $withoutCountry->setHouseNumber(2);
        $withoutCountry->setZip('3900');
        $withoutCountry->setCity('Overpelt');

        $withoutCityAndCountry = new \CultureFeed_Cdb_Data_Address_PhysicalAddress();
        $withoutCityAndCountry->setStreet('Jeugdlaan');
        $withoutCityAndCountry->setHouseNumber(2);
        $withoutCityAndCountry->setZip('3900');

        return [
            [$withoutStreet, 'The given cdbxml address is missing a street'],
            [$withoutZip, 'The given cdbxml address is missing a zip code'],
            [$withoutCity, 'The given cdbxml address is missing a city'],
            [$withoutCountry, 'The given cdbxml address is missing a country'],
            [$withoutCityAndCountry, 'The given cdbxml address is missing a city, country'],
        ];
    }
}
