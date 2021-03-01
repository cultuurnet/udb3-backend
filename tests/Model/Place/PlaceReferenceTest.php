<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

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

class PlaceReferenceTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_be_creatable_using_a_place_id()
    {
        $id = new UUID('38d78529-29b8-4635-a26e-51bbb2eba535');
        $reference = PlaceReference::createWithPlaceId($id);

        $this->assertEquals($id, $reference->getPlaceId());
        $this->assertNull($reference->getEmbeddedPlace());
    }

    /**
     * @test
     */
    public function it_should_be_creatable_using_a_place()
    {
        $id = new UUID('38d78529-29b8-4635-a26e-51bbb2eba535');

        $mainLanguage = new Language('nl');

        $title = new TranslatedTitle(
            $mainLanguage,
            new Title('Publiq')
        );

        $calendar = new PermanentCalendar(new OpeningHours());

        $address = new TranslatedAddress(
            $mainLanguage,
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );

        $terms = new Categories(
            new Category(
                new CategoryID('0.50.1.0.0'),
                new CategoryLabel('concertzaal'),
                new CategoryDomain('eventtype')
            )
        );

        $place = new ImmutablePlace(
            $id,
            $mainLanguage,
            $title,
            $calendar,
            $address,
            $terms
        );

        $reference = PlaceReference::createWithEmbeddedPlace($place);

        $this->assertEquals($id, $reference->getPlaceId());
        $this->assertEquals($place, $reference->getEmbeddedPlace());
    }
}
