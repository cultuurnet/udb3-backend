<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use ValueObjects\Geography\Country;
use ValueObjects\StringLiteral\StringLiteral;

class MajorInfoJSONDeserializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_major_info()
    {
        $majorInfoAsJson = file_get_contents(__DIR__ . '/../samples/place-major-info.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize(new StringLiteral($majorInfoAsJson));

        $expectedAddress = new Address(
            new Street('Kerkstraat 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->assertEquals('Test place', $majorInfo->getTitle());
        $this->assertEquals(new EventType('3CuHvenJ+EGkcvhXLg9Ykg', 'Archeologische Site'), $majorInfo->getType());
        $this->assertEquals($expectedAddress, $majorInfo->getAddress());
        $this->assertEquals(null, $majorInfo->getTheme());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $majorInfo->getCalendar());
    }
}
