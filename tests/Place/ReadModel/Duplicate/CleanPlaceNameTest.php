<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class CleanPlaceNameTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testTransform(string $title, string $expectedOutput): void
    {
        $cleanPlaceName = new CleanPlaceName();
        $this->assertEquals($expectedOutput, $cleanPlaceName->transform($this->createPlace($title)));
    }

    private function createPlace(string $title): ImmutablePlace
    {
        return new ImmutablePlace(
            new UUID('aadcee95-6180-4924-a8eb-ed829d4957a2'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title($title)
            ),
            new PermanentCalendar(new OpeningHours()),
            new TranslatedAddress(new Language('nl'), new Udb3Address(
                new Udb3Street('Kerkstraat 1'),
                new Udb3PostalCode('2000'),
                new Udb3Locality('Antwerpen'),
                new CountryCode('BE')
            )),
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                )
            )
        );
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
