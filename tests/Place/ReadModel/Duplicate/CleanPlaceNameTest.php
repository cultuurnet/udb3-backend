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
            // Test cases with various transformations
            'happy path - nothing changed' => ['BELGIE', 'belgie'],
            'Lowercase all letters' => ['BELGIE', 'belgie'],
            'Remove dots' => ['b.e.l', 'bel'],
            'Remove accents - simple' => ['belgië', 'belgie'],
            'Remove accents - complex 1' => ['Élèvê', 'eleve'],
            'Remove accents - complex 2' => ['garçon', 'garcon'],
            'Remove accents - complex 3' => ['àá', 'aa'],
            'Replace these symbols' => ["belgie\"'?&_,:(brussel)!", 'belgie brussel'],
            'Remove duplicate words' => ['De gezellige mosterpot - gezellige sfeer', 'de gezellige mosterpot - sfeer'],
            'Remove city name out of location name' => ['belgie antwerpen', 'belgie'],
        ];
    }
}
