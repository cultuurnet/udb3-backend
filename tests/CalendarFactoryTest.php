<?php

namespace CultuurNet\UDB3;

use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultureFeed_Cdb_Data_Calendar_TimestampList;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class CalendarFactoryTest extends TestCase
{
    /**
     * @var CalendarFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new CalendarFactory();
    }

    /**
     * @test
     */
    public function it_drops_timestamp_timeend_before_timestart()
    {
        $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
        $cdbCalendar->add(
            new CultureFeed_Cdb_Data_Calendar_Timestamp(
                "2016-12-16",
                "21:00:00",
                "05:00:00"
            )
        );

        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);

        $expectedTimeZone = new DateTimeZone('Europe/Brussels');
        $expectedStartDate = new DateTimeImmutable(
            '2016-12-16 21:00:00',
            $expectedTimeZone
        );
        $expectedEndDate = new DateTimeImmutable(
            '2016-12-17 05:00:00',
            $expectedTimeZone
        );

        $expectedCalendar = new Calendar(
            CalendarType::SINGLE(),
            $expectedStartDate,
            $expectedEndDate,
            [
                new Timestamp($expectedStartDate, $expectedEndDate),
            ]
        );

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     */
    public function it_can_create_a_calendar_from_a_weekscheme()
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

        $expectedCalendar = new Calendar(
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

        $weekScheme = \CultureFeed_Cdb_Data_Calendar_Weekscheme::parseFromCdbXml(
            simplexml_load_file(__DIR__ . '/week_scheme.xml')
        );

        $calendar = $this->factory->createFromWeekScheme($weekScheme);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     * @dataProvider timestampListDataProvider
     * @param CultureFeed_Cdb_Data_Calendar_TimestampList $cdbCalendar
     * @param Calendar $expectedCalendar
     */
    public function it_creates_calendars_with_timestamps_from_a_cdb_timestamp_list(
        CultureFeed_Cdb_Data_Calendar_TimestampList $cdbCalendar,
        Calendar $expectedCalendar
    ) {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);
        $this->assertEquals($expectedCalendar, $calendar);
    }

    public function timestampListDataProvider()
    {
        $timeZone = new DateTimeZone('Europe/Brussels');

        return [
            'import event with one timestamp: date, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2017-05-21 00:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-21 23:59:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 23:59:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with one timestamp: date + timestart, no timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:00",
                            "10:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with one timestamp: date + timestart + timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:00",
                            "11:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-21 11:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 11:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps: dates, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-06-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-07-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 00:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-07-21 23:59:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 23:59:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-06-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-06-21 23:59:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-07-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-07-21 23:59:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps in non-chronological order: dates, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-07-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-06-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 00:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-07-21 23:59:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-07-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-07-21 23:59:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 23:59:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-06-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-06-21 23:59:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps: dates + timestart, no timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:00",
                            "10:00:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-06-21",
                            "10:00:00",
                            "10:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-06-21 10:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-06-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-06-21 10:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps: dates + timestart + timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:00",
                            "11:30:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-06-21",
                            "10:00:00",
                            "11:30:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-06-21 11:30:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 11:30:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-06-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-06-21 11:30:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps: mixed: dates, with or without timestart/timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "00:00:00",
                            "23:59:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-06-21",
                            "10:00:00",
                            "10:00:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-07-21",
                            "10:00:00",
                            "11:30:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 00:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-07-21 11:30:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 23:59:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-06-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-06-21 10:00:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-07-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-07-21 11:30:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps with specific timeformat as one subevent' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-22",
                            "00:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-23",
                            "00:00:01",
                            "16:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-23 16:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-23 16:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps with specific timeformat as multiple subevents' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-22",
                            "00:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-23",
                            "00:00:01",
                            "16:00:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-24",
                            "10:00:02",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-25",
                            "00:00:02",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-26",
                            "00:00:02",
                            "16:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-26 16:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-23 16:00:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-24 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-26 16:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with multiple timestamps: mixed: dates with or without timestart/timeend and dates with specific timeformat imported as subevent' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "10:00:00",
                            "16:00:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-21",
                            "20:00:00",
                            "01:00:00"
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-23",
                            "10:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-24",
                            "00:00:01",
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            "2017-05-25",
                            "00:00:01",
                            "16:00:00"
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    new DateTimeImmutable(
                        '2017-05-21 10:00:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2017-05-25 16:00:00',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 16:00:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-21 20:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-22 01:00:00',
                                $timeZone
                            )
                        ),
                        new Timestamp(
                            new DateTimeImmutable(
                                '2017-05-23 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-25 16:00:00',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with timestamp with seconds but no index' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2011-11-11',
                            '11:11:11',
                            '12:12:12'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2011-11-11 11:11:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2011-11-11 12:12:12',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2011-11-11 11:11:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2011-11-11 12:12:12',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
            'import event with timestamp with seconds but no index and no end time' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2011-11-11',
                            '11:11:11',
                            null
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new Calendar(
                    CalendarType::SINGLE(),
                    new DateTimeImmutable(
                        '2011-11-11 11:11:00',
                        $timeZone
                    ),
                    new DateTimeImmutable(
                        '2011-11-11 11:11:11',
                        $timeZone
                    ),
                    [
                        new Timestamp(
                            new DateTimeImmutable(
                                '2011-11-11 11:11:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2011-11-11 11:11:11',
                                $timeZone
                            )
                        ),
                    ]
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider periodListDataProvider
     * @param \CultureFeed_Cdb_Data_Calendar_PeriodList $cdbCalendar
     * @param Calendar $expectedCalendar
     */
    public function it_creates_a_periodic_calendar_from_cdb_calendar_with_period_list(
        \CultureFeed_Cdb_Data_Calendar_PeriodList $cdbCalendar,
        Calendar $expectedCalendar
    ) {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @param string $xmlContent
     * @return \CultureFeed_Cdb_Data_Calendar_PeriodList
     */
    private function createPeriodListFromXML($xmlContent)
    {
        $xmlElement = new \SimpleXMLElement($xmlContent);
        return \CultureFeed_Cdb_Data_Calendar_PeriodList::parseFromCdbXml($xmlElement);
    }

    public function periodListDataProvider()
    {
        $timeZone = new DateTimeZone('Europe/Brussels');

        return [
            'import event with period: datefrom + dateto, no weekscheme' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/periodic/calendar_udb2_import_example_1101.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERIODIC(),
                    new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                    new DateTimeImmutable('2017-12-31 00:00:00', $timeZone)
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme only openingtimes from' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/periodic/calendar_udb2_import_example_1201.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERIODIC(),
                    new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                    new DateTimeImmutable('2017-12-31 00:00:00', $timeZone),
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme openingtimes from + to' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/periodic/calendar_udb2_import_example_1301.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERIODIC(),
                    new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                    new DateTimeImmutable('2017-12-31 00:00:00', $timeZone),
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(22), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(20), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme mix openingtimes from + to' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/periodic/calendar_udb2_import_example_1401.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERIODIC(),
                    new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                    new DateTimeImmutable('2017-12-31 00:00:00', $timeZone),
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(22), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider permanentCalendarDataProvider
     * @param \CultureFeed_Cdb_Data_Calendar_Permanent $cdbCalendar
     * @param Calendar $expectedCalendar
     */
    public function it_creates_a_permanent_calendar_from_cdb_calendar(
        \CultureFeed_Cdb_Data_Calendar_Permanent $cdbCalendar,
        Calendar $expectedCalendar
    ) {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @param string $xmlContent
     * @return \CultureFeed_Cdb_Data_Calendar_Permanent
     */
    private function createPermanentCalendarFromXML($xmlContent)
    {
        $xmlElement = new \SimpleXMLElement($xmlContent);
        return \CultureFeed_Cdb_Data_Calendar_Permanent::parseFromCdbXml($xmlElement);
    }

    /**
     * @return array
     */
    public function permanentCalendarDataProvider()
    {
        return [
            'import permanent event, no weekscheme as periodic event' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/permanent/calendar_udb2_import_example_1501.xml')
                ),
                'expectedCalendar' => new Calendar(CalendarType::PERMANENT()),
            ],
            'import permanent event with weekscheme only openingtimes from' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/permanent/calendar_udb2_import_example_1601.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERMANENT(),
                    null,
                    null,
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
            'import permanent event with weekscheme openingtimes from + to' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/permanent/calendar_udb2_import_example_1701.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERMANENT(),
                    null,
                    null,
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(22), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(20), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
            'import permanent event with weekscheme mix openingtimes from + to' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    file_get_contents(__DIR__ . '/Calendar/samples/permanent/calendar_udb2_import_example_1801.xml')
                ),
                'expectedCalendar' => new Calendar(
                    CalendarType::PERMANENT(),
                    null,
                    null,
                    [],
                    [
                        new OpeningHour(
                            new OpeningTime(new Hour(20), new Minute(30)),
                            new OpeningTime(new Hour(22), new Minute(30)),
                            new DayOfWeekCollection(
                                DayOfWeek::MONDAY(),
                                DayOfWeek::THURSDAY(),
                                DayOfWeek::FRIDAY(),
                                DayOfWeek::SATURDAY()
                            )
                        ),
                        new OpeningHour(
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new OpeningTime(new Hour(16), new Minute(0)),
                            new DayOfWeekCollection(
                                DayOfWeek::SUNDAY()
                            )
                        ),
                    ]
                ),
            ],
        ];
    }
}
