<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
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
        $this->assertEquals(new EventType('3CuHvenJ+EGkcvhXLg9Ykg', 'Archeologische Site'), $createPlace->getType());
        $this->assertEquals($expectedAddress, $createPlace->getAddress());
        $this->assertEquals(new Calendar(CalendarType::permanent()), $createPlace->getCalendar());
    }
}
