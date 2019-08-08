<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Event;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class CreateEventJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_create_event_with_main_language()
    {
        $createEventAsJson = file_get_contents(__DIR__ . '/../samples/event-create-with-main-language.json');

        $createEventJSONDeserializer = new CreateEventJSONDeserializer();

        $createEvent = $createEventJSONDeserializer->deserialize(new StringLiteral($createEventAsJson));

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals(new Language('en'), $createEvent->getMainLanguage());
        $this->assertEquals('talking title', $createEvent->getTitle());
        $this->assertEquals(new EventType('0.17.0.0.0', 'Route'), $createEvent->getType());
        $this->assertEquals($expectedLocation, $createEvent->getLocation());
        $this->assertEquals(new Calendar(CalendarType::PERMANENT()), $createEvent->getCalendar());
    }
}
