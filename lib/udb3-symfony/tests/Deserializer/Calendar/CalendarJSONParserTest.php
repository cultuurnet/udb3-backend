<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Timestamp;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class CalendarJSONParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $updateCalendarAsArray;

    /**
     * @var CalendarJSONParser
     */
    private $calendarJSONParser;

    protected function setUp()
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_all_fields.json');
        $this->updateCalendarAsArray = json_decode($updateCalendar, true);

        $this->calendarJSONParser = new CalendarJSONParser();
    }

    /**
     * @test
     */
    public function it_can_get_the_start_date()
    {
        $startDate = \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T09:00:00+01:00');

        $this->assertEquals(
            $startDate,
            $this->calendarJSONParser->getStartDate(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_null_when_start_date_is_missing()
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_missing_start_and_end.json');
        $updateCalendarAsArray = json_decode($updateCalendar, true);

        $this->assertNull(
            $this->calendarJSONParser->getStartDate(
                $updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_end_date()
    {
        $endDate = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-10T16:00:00+01:00');

        $this->assertEquals(
            $endDate,
            $this->calendarJSONParser->getEndDate(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_null_when_end_date_is_missing()
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_missing_start_and_end.json');
        $updateCalendarAsArray = json_decode($updateCalendar, true);

        $this->assertNull(
            $this->calendarJSONParser->getEndDate(
                $updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_time_spans()
    {
        $startDatePeriod1 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T09:00:00+01:00');
        $endDatePeriod1 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-01T16:00:00+01:00');

        $startDatePeriod2 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-03T09:00:00+01:00');
        $endDatePeriod2 = \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-10T16:00:00+01:00');

        $timeSpans = [
            new TimeSpan(
                $startDatePeriod1,
                $endDatePeriod1
            ),
            new TimeSpan(
                $startDatePeriod2,
                $endDatePeriod2
            ),
        ];

        $this->assertEquals(
            $timeSpans,
            $this->calendarJSONParser->getTimeSpans(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_time_spans_when_json_is_missing_an_end_property()
    {
        $calendarData = json_decode(
            file_get_contents(__DIR__ . '/samples/calendar_missing_time_span_end.json'),
            true
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getTimeSpans($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_time_spans_when_json_is_missing_a_start_property()
    {
        $calendarData = json_decode(
            file_get_contents(__DIR__ . '/samples/calendar_missing_time_span_start.json'),
            true
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getTimeSpans($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_opening_hours()
    {
        $openingHours = [
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(17),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::TUESDAY(),
                    DayOfWeek::WEDNESDAY(),
                    DayOfWeek::THURSDAY(),
                    DayOfWeek::FRIDAY()
                )
            ),
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(12),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::SATURDAY()
                )
            ),
        ];

        $this->assertEquals(
            $openingHours,
            $this->calendarJSONParser->getOpeningHours(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_opening_hours_when_fields_are_missing()
    {
        $calendarData = json_decode(
            file_get_contents(__DIR__ . '/samples/calendar_with_opening_hours_but_missing_fields.json'),
            true
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getOpeningHours($calendarData)
        );
    }
}
