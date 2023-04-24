<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use PHPUnit\Framework\TestCase;

final class DefaultAddressFormatterTest extends TestCase
{
    /**
     * @test
     */
    public function it_formats_addresses()
    {
        $formatter = new FullAddressFormatter();

        $address = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $expectedString = 'Martelarenlaan 1, 3000 Leuven, BE';

        $this->assertEquals($expectedString, $formatter->format($address));
    }

    /**
     * @test
     */
    public function it_formats_addresses_with_empty_street()
    {
        $formatter = new FullAddressFormatter();

        $address = new Address(
            new Street(''),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $expectedString = '3000 Leuven, BE';

        $this->assertEquals($expectedString, $formatter->format($address));
    }
}
