<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
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
        $majorInfoAsJson = SampleFiles::read(__DIR__ . '/../samples/event-major-info.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize($majorInfoAsJson);

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals(new Title('talking title'), $majorInfo->getTitle());
        $this->assertEquals(new EventType('0.17.0.0.0', 'Route'), $majorInfo->getType());
        $this->assertEquals($expectedLocation, $majorInfo->getLocation());
        $this->assertEquals(new Calendar(CalendarType::permanent()), $majorInfo->getCalendar());
    }

    /**
     * @test
     */
    public function it_can_serialize_major_info_with_a_nested_location_id(): void
    {
        $majorInfoAsJson = SampleFiles::read(__DIR__ . '/../samples/event-major-info-with-nested-location-id.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize($majorInfoAsJson);

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals(new Title('talking title'), $majorInfo->getTitle());
        $this->assertEquals(new EventType('0.17.0.0.0', 'Route'), $majorInfo->getType());
        $this->assertEquals($expectedLocation, $majorInfo->getLocation());
        $this->assertEquals(new Calendar(CalendarType::permanent()), $majorInfo->getCalendar());
    }
}
