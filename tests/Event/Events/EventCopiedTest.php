<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventCopiedTest extends TestCase
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var string
     */
    private $originalEventId;

    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @var EventCopied
     */
    private $eventCopied;

    protected function setUp(): void
    {
        $this->eventId = 'e49430ca-5729-4768-8364-02ddb385517a';

        $this->originalEventId = '27105ae2-7e1c-425e-8266-4cb86a546159';

        // Microseconds are not taken into account when serializing, but since
        // PHP 7.1 DateTime incorporates them. We set the microseconds
        // explicitly to 0 in this test to make it pass.
        // See http://php.net/manual/en/migration71.incompatible.php#migration71.incompatible.datetime-microseconds.
        $this->calendar = new Calendar(
            CalendarType::PERIODIC(),
            new DateTime('2017-01-24T21:47:26.000000+0000'),
            new DateTime('2020-01-24T21:47:26.000000+0000')
        );

        $this->eventCopied = new EventCopied(
            $this->eventId,
            $this->originalEventId,
            $this->calendar
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id(): void
    {
        $this->assertEquals(
            $this->eventId,
            $this->eventCopied->getItemId()
        );
    }

    /**
     * @test
     */
    public function it_stores_an_original_event_id(): void
    {
        $this->assertEquals(
            $this->originalEventId,
            $this->eventCopied->getOriginalEventId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_calendar(): void
    {
        $this->assertEquals(
            $this->calendar,
            $this->eventCopied->getCalendar()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_to_an_array(): void
    {
        $this->assertEquals(
            [
                'item_id' => $this->eventId,
                'original_event_id' => $this->originalEventId,
                'calendar' => $this->calendar->serialize(),
            ],
            $this->eventCopied->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_from_an_array(): void
    {
        $this->assertEquals(
            $this->eventCopied,
            EventCopied::deserialize(
                [
                    'item_id' => $this->eventId,
                    'original_event_id' => $this->originalEventId,
                    'calendar' => $this->calendar->serialize(),
                ]
            )
        );
    }
}
