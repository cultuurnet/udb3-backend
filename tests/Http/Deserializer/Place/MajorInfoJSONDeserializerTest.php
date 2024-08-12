<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class MajorInfoJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_major_info(): void
    {
        $majorInfoAsJson = SampleFiles::read(__DIR__ . '/../samples/place-major-info.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize($majorInfoAsJson);

        $expectedAddress = new Address(
            new Street('Kerkstraat 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $this->assertEquals(new Title('Test place'), $majorInfo->getTitle());
        $this->assertEquals(new EventType('3CuHvenJ+EGkcvhXLg9Ykg', 'Archeologische Site'), $majorInfo->getType());
        $this->assertEquals($expectedAddress, $majorInfo->getAddress());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $majorInfo->getCalendar());
    }
}
