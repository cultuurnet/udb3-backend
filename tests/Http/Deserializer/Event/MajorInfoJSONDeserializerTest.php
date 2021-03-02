<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class MajorInfoJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_major_info()
    {
        $majorInfoAsJson = file_get_contents(__DIR__ . '/../samples/event-major-info.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize(new StringLiteral($majorInfoAsJson));

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals('talking title', $majorInfo->getTitle());
        $this->assertEquals(new EventType('0.17.0.0.0', 'Route'), $majorInfo->getType());
        $this->assertEquals($expectedLocation, $majorInfo->getLocation());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $majorInfo->getCalendar());
    }

    /**
     * @test
     */
    public function it_can_serialize_major_info_with_a_nested_location_id()
    {
        $majorInfoAsJson = file_get_contents(__DIR__ . '/../samples/event-major-info-with-nested-location-id.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize(new StringLiteral($majorInfoAsJson));

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals('talking title', $majorInfo->getTitle());
        $this->assertEquals(new EventType('0.17.0.0.0', 'Route'), $majorInfo->getType());
        $this->assertEquals($expectedLocation, $majorInfo->getLocation());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $majorInfo->getCalendar());
    }
}
