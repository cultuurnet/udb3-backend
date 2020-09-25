<?php

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
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
use ValueObjects\Geography\Country;

class Udb3ModelToLegacyPlaceAdapterTest extends TestCase
{
    /**
     * @var Udb3ModelToLegacyPlaceAdapter
     */
    private $adapter;

    public function setUp()
    {
        $place = new ImmutablePlace(
            new UUID('6ba87a6b-efea-4467-9e87-458d145384d9'),
            new Language('nl'),
            new TranslatedTitle(new Language('nl'), new Title('Voorbeeld titel')),
            new PermanentCalendar(new OpeningHours()),
            new TranslatedAddress(
                new Language('nl'),
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    new CountryCode('BE')
                )
            ),
            new Categories(
                new Category(
                    new CategoryID('0.14.0.0.0'),
                    new CategoryLabel('Monument'),
                    new CategoryDomain('eventtype')
                )
            )
        );

        /** @var ImmutablePlace $place */
        $place = $place->withAvailableFrom(
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T10:00:00+01:00')
        );

        $translatedAddress = $place->getAddress()
            ->withTranslation(
                new Language('fr'),
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    new CountryCode('BE')
                )
            )
            ->withTranslation(
                new Language('en'),
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussels'),
                    new CountryCode('BE')
                )
            );

        $place = $place->withAddress($translatedAddress);

        $this->adapter = new Udb3ModelToLegacyPlaceAdapter($place);
    }

    /**
     * @test
     */
    public function it_should_return_an_address()
    {
        $expected = new \CultuurNet\UDB3\Address\Address(
            new \CultuurNet\UDB3\Address\Street('Henegouwenkaai 41-43'),
            new \CultuurNet\UDB3\Address\PostalCode('1080'),
            new \CultuurNet\UDB3\Address\Locality('Brussel'),
            new Country(\ValueObjects\Geography\CountryCode::fromNative('BE'))
        );
        $actual = $this->adapter->getAddress();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_address_translations()
    {
        $expected = [
            'fr' => new \CultuurNet\UDB3\Address\Address(
                new \CultuurNet\UDB3\Address\Street('Quai du Hainaut 41-43'),
                new \CultuurNet\UDB3\Address\PostalCode('1080'),
                new \CultuurNet\UDB3\Address\Locality('Bruxelles'),
                new Country(\ValueObjects\Geography\CountryCode::fromNative('BE'))
            ),
            'en' => new \CultuurNet\UDB3\Address\Address(
                new \CultuurNet\UDB3\Address\Street('Henegouwenkaai 41-43'),
                new \CultuurNet\UDB3\Address\PostalCode('1080'),
                new \CultuurNet\UDB3\Address\Locality('Brussels'),
                new Country(\ValueObjects\Geography\CountryCode::fromNative('BE'))
            ),
        ];
        $actual = $this->adapter->getAddressTranslations();
        $this->assertEquals($expected, $actual);
    }
}
