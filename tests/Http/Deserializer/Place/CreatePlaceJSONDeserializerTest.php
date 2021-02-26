<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\StringLiteral\StringLiteral;

class CreatePlaceJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_create_place_with_main_language_info()
    {
        $createPlaceAsJson = file_get_contents(__DIR__ . '/../samples/place-create-with-main-language.json');

        $createPlaceJSONDeserializer = new CreatePlaceJSONDeserializer();

        $createPlace = $createPlaceJSONDeserializer->deserialize(new StringLiteral($createPlaceAsJson));

        $expectedAddress = new Address(
            new Street('Kerkstraat 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->assertEquals(new Language('en'), $createPlace->getMainLanguage());
        $this->assertEquals('Test place', $createPlace->getTitle());
        $this->assertEquals(new EventType('3CuHvenJ+EGkcvhXLg9Ykg', 'Archeologische Site'), $createPlace->getType());
        $this->assertEquals($expectedAddress, $createPlace->getAddress());
        $this->assertEquals(null, $createPlace->getTheme());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $createPlace->getCalendar());
    }
}
