<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UpdateCalendarTest extends TestCase
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @var UpdateCalendar
     */
    private $updateCalendar;

    protected function setUp()
    {
        $this->eventId = '0f4ea9ad-3681-4f3b-adc2-4b8b00dd845a';

        $this->calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $this->updateCalendar = new UpdateCalendar(
            $this->eventId,
            $this->calendar
        );
    }

    /**
     * @test
     */
    public function it_stores_an_event_id()
    {
        $this->assertEquals($this->eventId, $this->updateCalendar->getItemId());
    }

    /**
     * @test
     */
    public function it_stores_a_calendar()
    {
        $this->assertEquals($this->calendar, $this->updateCalendar->getCalendar());
    }

    /**
     * @test
     */
    public function is_stores_aanbod_bewerken_permission()
    {
        $this->assertEquals(
            Permission::AANBOD_BEWERKEN(),
            $this->updateCalendar->getPermission()
        );
    }
}
