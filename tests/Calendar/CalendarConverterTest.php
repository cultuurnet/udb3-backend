<?php

namespace CultuurNet\UDB3\Calendar;

use CultureFeed_Cdb_Data_Calendar_Period;
use CultureFeed_Cdb_Data_Calendar_PeriodList;
use CultureFeed_Cdb_Data_Calendar_Permanent;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Timestamp;
use DateTime;
use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class CalendarConverterTest extends TestCase
{
    /**
     * @var CalendarConverter
     */
    private $converter;

    public function setUp()
    {
        $this->converter = new CalendarConverter();
    }

    /**
     * @test
     */
    public function it_converts_a_permanent_calendar_as_a_cdb_calendar_object()
    {
        $calendar = new Calendar(CalendarType::PERMANENT());

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_Permanent();

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with one timestamp, start and enddate on same day
     * @test
     */
    public function it_converts_a_calendar_with_single_timestamp_as_a_cdb_calendar_object()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-24',
            '09:00:00',
            '19:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            new DateTime('2017-01-24T08:00:00.000000+0000'),
            new DateTime('2017-01-24T18:00:00.000000+0000'),
            [
                new Timestamp(
                    new DateTime('2017-01-24T08:00:00.000000+0000'),
                    new DateTime('2017-01-24T18:00:00.000000+0000')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with multiple timestamps, start and enddate on same day
     * @test
     */
    public function it_converts_a_calendar_with_multiple_timestamps_as_a_cdb_calendar_object()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-24',
            '09:00:00',
            '19:00:00'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-25',
            '09:00:00',
            '19:00:00'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-26',
            '09:00:00',
            '19:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            new DateTime('2017-01-24T08:00:00.000000+0000'),
            new DateTime('2017-01-26T18:00:00.000000+0000'),
            [
                new Timestamp(
                    new DateTime('2017-01-24T08:00:00.000000+0000'),
                    new DateTime('2017-01-24T18:00:00.000000+0000')
                ),
                new Timestamp(
                    new DateTime('2017-01-25T08:00:00.000000+0000'),
                    new DateTime('2017-01-25T18:00:00.000000+0000')
                ),
                new Timestamp(
                    new DateTime('2017-01-26T08:00:00.000000+0000'),
                    new DateTime('2017-01-26T18:00:00.000000+0000')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with multiple timestamps, enddate one day later
     * @test
     */
    public function it_converts_a_calendar_with_multiple_timestamps_that_end_the_next_day_as_a_cdb_calendar_object()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-24',
            '19:00:00',
            '03:00:00'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-01-25',
            '19:00:00',
            '03:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            new DateTime('2017-01-24T18:00:00.000000+0000'),
            new DateTime('2017-01-25T02:00:00.000000+0000'),
            [
                new Timestamp(
                    new DateTime('2017-01-24T18:00:00.000000+0000'),
                    new DateTime('2017-01-25T02:00:00.000000+0000')
                ),
                new Timestamp(
                    new DateTime('2017-01-25T18:00:00.000000+0000'),
                    new DateTime('2017-01-26T02:00:00.000000+0000')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * TODO: add to features
     * @scenario permanent event with multiple sets of opening hours
     * @test
     */
    public function it_converts_permanent_calendar_with_weekscheme_as_a_cdb_calendar_object()
    {
        $weekDays = new DayOfWeekCollection(
            DayOfWeek::MONDAY(),
            DayOfWeek::TUESDAY(),
            DayOfWeek::WEDNESDAY(),
            DayOfWeek::THURSDAY(),
            DayOfWeek::FRIDAY()
        );

        $weekendDays = new DayOfWeekCollection(
            DayOfWeek::SATURDAY(),
            DayOfWeek::SUNDAY()
        );

        $calendar = new Calendar(
            CalendarType::PERMANENT(),
            null,
            null,
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(9), new Minute(0)),
                    new OpeningTime(new Hour(12), new Minute(0)),
                    $weekDays
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(13), new Minute(0)),
                    new OpeningTime(new Hour(17), new Minute(0)),
                    $weekDays
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(10), new Minute(0)),
                    new OpeningTime(new Hour(16), new Minute(0)),
                    $weekendDays
                ),
            ]
        );

        $expectedCalendar = new CultureFeed_Cdb_Data_Calendar_Permanent();
        $weekScheme = \CultureFeed_Cdb_Data_Calendar_Weekscheme::parseFromCdbXml(
            simplexml_load_file(__DIR__ . '/../week_scheme.xml')
        );
        $expectedCalendar->setWeekScheme($weekScheme);

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario periodic event with multiple sets of openinghours
     * @test
     */
    public function it_converts_periodic_calendar_with_weekscheme_as_a_cdb_calendar_object()
    {
        $weekDays = new DayOfWeekCollection(
            DayOfWeek::MONDAY(),
            DayOfWeek::TUESDAY(),
            DayOfWeek::WEDNESDAY(),
            DayOfWeek::THURSDAY(),
            DayOfWeek::FRIDAY()
        );

        $weekendDays = new DayOfWeekCollection(
            DayOfWeek::SATURDAY(),
            DayOfWeek::SUNDAY()
        );

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            new DateTime('2017-01-24T00:00:00.000000+0000'),
            new DateTime('2018-01-24T00:00:00.000000+0000'),
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(9), new Minute(0)),
                    new OpeningTime(new Hour(12), new Minute(0)),
                    $weekDays
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(13), new Minute(0)),
                    new OpeningTime(new Hour(17), new Minute(0)),
                    $weekDays
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(10), new Minute(0)),
                    new OpeningTime(new Hour(16), new Minute(0)),
                    $weekendDays
                ),
            ]
        );

        $weekScheme = \CultureFeed_Cdb_Data_Calendar_Weekscheme::parseFromCdbXml(
            simplexml_load_file(__DIR__ . '/../week_scheme.xml')
        );

        $expectedPeriod = new CultureFeed_Cdb_Data_Calendar_Period('2017-01-24', '2018-01-24');
        $expectedPeriod->setWeekScheme($weekScheme);
        $expectedCalendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();
        $expectedCalendar->add($expectedPeriod);

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario periodic event with one set of openinghours
     * @test
     */
    public function it_converts_a_periodic_calendar_with_a_single_set_of_opening_hours_as_a_cdb_calendar_with_week_scheme()
    {
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            new DateTime('2017-01-24T00:00:00.000000+0000'),
            new DateTime('2018-01-24T00:00:00.000000+0000'),
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(9), new Minute(0)),
                    new OpeningTime(new Hour(17), new Minute(0)),
                    new DayOfWeekCollection(
                        DayOfWeek::MONDAY()
                    )
                ),
            ]
        );

        $openSchemeDay = new \CultureFeed_Cdb_Data_Calendar_SchemeDay(
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::MONDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_OPEN
        );
        $openSchemeDay->addOpeningTime(new \CultureFeed_Cdb_Data_Calendar_OpeningTime('09:00:00', '17:00:00'));

        $closedDays = [
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::TUESDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::WEDNESDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::THURSDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::FRIDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::SATURDAY,
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::SUNDAY,
        ];
        $weekScheme = new \CultureFeed_Cdb_Data_Calendar_Weekscheme();
        $weekScheme->setDay(
            \CultureFeed_Cdb_Data_Calendar_SchemeDay::MONDAY,
            $openSchemeDay
        );
        array_walk($closedDays, function ($day) use (&$weekScheme) {
            $weekScheme->setDay(
                $day,
                new \CultureFeed_Cdb_Data_Calendar_SchemeDay(
                    $day,
                    \CultureFeed_Cdb_Data_Calendar_SchemeDay::SCHEMEDAY_OPEN_TYPE_CLOSED
                )
            );
        });

        $expectedPeriod = new CultureFeed_Cdb_Data_Calendar_Period('2017-01-24', '2018-01-24');
        $expectedPeriod->setWeekScheme($weekScheme);
        $expectedCalendar = new CultureFeed_Cdb_Data_Calendar_PeriodList();
        $expectedCalendar->add($expectedPeriod);

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with one timestamp, enddate one day later
     * @test
     */
    public function it_converts_a_calendar_with_a_timestamp_that_ends_the_next_day_as_single_cdb_timestamp()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-26',
            '21:00:00',
            '02:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            null,
            null,
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-26T21:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-27T02:00:00+02:00')
                ),
            ],
            []
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with one timestamp, enddate more than one day later
     * @test
     */
    public function it_converts_a_calendar_with_a_single_timestamp_that_spans_3_days_as_multiple_cdb_timestamps()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-26',
            '21:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-27',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-28',
            '00:00:01',
            '02:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            null,
            null,
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-26T21:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-28T02:00:00+02:00')
                ),
            ],
            []
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with one timestamp, enddate more than one day later
     * @test
     */
    public function it_converts_a_calendar_with_a_single_timestamp_that_spans_a_whole_week_as_multiple_cdb_timestamps()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();

        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-01',
            '09:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-02',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-03',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-04',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-05',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-06',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-07',
            '00:00:01',
            '18:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            null,
            null,
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-01T09:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-07T18:00:00+02:00')
                ),
            ],
            []
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with multiple timestamps, start and enddate more than one day apart
     * @test
     */
    public function it_converts_a_calendar_with_multiple_timestamps_that_span_more_than_two_days_as_multiple_indexed_cdb_timestamps()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-01',
            '09:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-02',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-03',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-04',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-05',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-06',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-07',
            '00:00:01',
            '18:00:00'
        ));

        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-26',
            '21:00:02'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-27',
            '00:00:02'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-28',
            '00:00:02',
            '02:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2017-05-01T09:00:00+02:00'),
            DateTime::createFromFormat(DateTime::ATOM, '2017-05-28T02:00:00+02:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-01T09:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-07T18:00:00+02:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-26T21:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-28T02:00:00+02:00')
                ),
            ],
            []
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with multiple timestamps (MIX): start and enddate on the same day, enddate one day later,
     *  enddate more than one day later
     * @test
     */
    public function it_converts_a_calendar_with_multiple_timestamp_of_various_duration_as_multiple_indexed_cdb_timestamps()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-25',
            '10:00:00',
            '16:00:00'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-25',
            '20:00:00',
            '01:00:00'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-06-28',
            '10:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-06-29',
            '00:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-06-30',
            '00:00:01',
            '16:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2017-05-25T10:00:00+02:00'),
            DateTime::createFromFormat(DateTime::ATOM, '2017-06-30T16:00:00+02:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-25T10:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-25T16:00:00+02:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-25T20:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-05-26T01:00:00+02:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2017-06-28T10:00:00+02:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2017-06-30T16:00:00+02:00')
                ),
            ],
            []
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @feature calendar_udb3_update.feature
     * @scenario event with one timestamp, last all day
     * @test
     */
    public function it_converts_a_calendar_that_lasts_all_day_as_a_single_cdb_timestamp()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-05-25',
            '00:00:00',
            '23:59:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            new DateTime('2017-05-25T00:00:00+02:00'),
            new DateTime('2017-05-25T23:59:00+02:00'),
            [
                new Timestamp(
                    new DateTime('2017-05-25T00:00:00+02:00'),
                    new DateTime('2017-05-25T23:59:00+02:00')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @test
     */
    public function it_converts_a_calendar_that_spans_two_days_and_start_and_ends_on_the_same_time_of_day()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-07-20',
            '20:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-07-21',
            '00:00:01',
            '20:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            new DateTime('2017-07-20T20:00:00+02:00'),
            new DateTime('2017-07-21T20:00:00+02:00'),
            [
                new Timestamp(
                    new DateTime('2017-07-20T20:00:00+02:00'),
                    new DateTime('2017-07-21T20:00:00+02:00')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }

    /**
     * @test
     */
    public function it_converts_a_calendar_that_spans_two_days_and_the_time_of_day_of_the_end_date_is_later_than_the_start_date()
    {
        $expectedCalendar = new \CultureFeed_Cdb_Data_Calendar_TimestampList();
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-07-20',
            '20:00:01'
        ));
        $expectedCalendar->add(new \CultureFeed_Cdb_Data_Calendar_Timestamp(
            '2017-07-21',
            '00:00:01',
            '21:00:00'
        ));

        $calendar = new Calendar(
            CalendarType::SINGLE(),
            new DateTime('2017-07-20T20:00:00+02:00'),
            new DateTime('2017-07-21T21:00:00+02:00'),
            [
                new Timestamp(
                    new DateTime('2017-07-20T20:00:00+02:00'),
                    new DateTime('2017-07-21T21:00:00+02:00')
                ),
            ]
        );

        $cdbCalendar = $this->converter->toCdbCalendar($calendar);

        $this->assertEquals($expectedCalendar, $cdbCalendar);
    }
}
