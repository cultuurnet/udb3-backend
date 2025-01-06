<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Formatter;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use PHPUnit\Framework\TestCase;

class LocalityAddressFormatterTest extends TestCase
{
    /**
     * @test
     */
    public function it_formats_addresses(): void
    {
        $formatter = new LocalityAddressFormatter();

        $address = new Address(
            new Street('Martelarenlaan 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $expectedString = '3000 Leuven, BE';

        $this->assertEquals($expectedString, $formatter->format($address));
    }
}
