<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;
use PHPUnit\Framework\TestCase;

class CleanPlaceNameTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testTransform(string $title, string $expectedOutput): void
    {
        $address = new Udb3Address(
            new Udb3Street('Kerkstraat 1'),
            new Udb3PostalCode('2000'),
            new Udb3Locality('Antwerpen'),
            new CountryCode('BE')
        );

        $this->assertEquals($expectedOutput, CleanPlaceName::transform($address, $title));
    }

    public function dataProvider(): array
    {
        return [
            ['Café de lindekens', 'Café de lindekens'],
            ['<b>Café de lindekens</b>', 'Café de lindekens'],
            ['\u0062\u0061\u0072 la cantina', 'bar la cantina'],
            ['aan de hoek van het kruispunt om 15 uur vertrekken we aan de hoek van het kruispunt om 15 uur vertrekken we aan de hoek van het kruispunt om 15 uur vertrekken we', ''],
            ['Speelplaats aan de Kerkstraat 1', ''],
            ['Café de lindekens BE', 'Café de lindekens Belgium'],
            ['BE Café de lindekens', 'Belgium Café de lindekens'],
            ['dit is belachelijk om te veranderen', 'dit is belachelijk om te veranderen'],
            ['FR', ''],
            ['France', ''],
        ];
    }
}
