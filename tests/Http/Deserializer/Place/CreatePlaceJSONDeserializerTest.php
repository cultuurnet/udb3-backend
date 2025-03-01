<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class CreatePlaceJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_create_place_with_main_language_info(): void
    {
        $createPlaceAsJson = SampleFiles::read(__DIR__ . '/../samples/place-create-with-main-language.json');

        $createPlaceJSONDeserializer = new CreatePlaceJSONDeserializer();

        $createPlace = $createPlaceJSONDeserializer->deserialize($createPlaceAsJson);

        $expectedAddress = new Address(
            new Street('Kerkstraat 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $this->assertEquals(new Language('en'), $createPlace->getMainLanguage());
        $this->assertEquals(new Title('Test place'), $createPlace->getTitle());
        $this->assertEquals(
            new Category(new CategoryID('3CuHvenJ+EGkcvhXLg9Ykg'), new CategoryLabel('Archeologische Site'), CategoryDomain::eventType()),
            $createPlace->getType()
        );
        $this->assertEquals($expectedAddress, $createPlace->getAddress());
        $this->assertEquals(new PermanentCalendar(new OpeningHours()), $createPlace->getCalendar());
    }
}
