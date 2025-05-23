<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultureFeed_Cdb_Data_Calendar_Timestamp;
use CultureFeed_Cdb_Data_Calendar_TimestampList;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\SampleFiles;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class CalendarFactoryTest extends TestCase
{
    private CalendarFactory $factory;

    public function setUp(): void
    {
        $this->factory = new CalendarFactory();
    }

    /**
     * @test
     */
    public function it_drops_timestamp_timeend_before_timestart(): void
    {
        $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
        $cdbCalendar->add(
            new CultureFeed_Cdb_Data_Calendar_Timestamp(
                '2016-12-16',
                '21:00:00',
                '05:00:00'
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

        $expectedCalendar = new SingleSubEventCalendar(
            SubEvent::createAvailable(new DateRange($expectedStartDate, $expectedEndDate))
        );

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     */
    public function it_can_create_a_calendar_from_a_weekscheme(): void
    {
        $weekDays = new Days(
            Day::monday(),
            Day::tuesday(),
            Day::wednesday(),
            Day::thursday(),
            Day::friday()
        );

        $weekendDays = new Days(
            Day::saturday(),
            Day::sunday()
        );

        $expectedCalendar = new PermanentCalendar(
            new OpeningHours(
                new OpeningHour(
                    $weekDays,
                    new Time(new Hour(9), new Minute(0)),
                    new Time(new Hour(12), new Minute(0))
                ),
                new OpeningHour(
                    $weekDays,
                    new Time(new Hour(13), new Minute(0)),
                    new Time(new Hour(17), new Minute(0))
                ),
                new OpeningHour(
                    $weekendDays,
                    new Time(new Hour(10), new Minute(0)),
                    new Time(new Hour(16), new Minute(0))
                )
            )
        );

        $weekScheme = \CultureFeed_Cdb_Data_Calendar_Weekscheme::parseFromCdbXml(
            simplexml_load_file(__DIR__ . '/samples/week_scheme.xml')
        );

        $calendar = $this->factory->createFromWeekScheme($weekScheme);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     * @dataProvider timestampListDataProvider
     */
    public function it_creates_calendars_with_timestamps_from_a_cdb_timestamp_list(
        CultureFeed_Cdb_Data_Calendar_TimestampList $cdbCalendar,
        Calendar $expectedCalendar
    ): void {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);
        $this->assertEquals($expectedCalendar, $calendar);
    }

    public function timestampListDataProvider(): array
    {
        $timeZone = new DateTimeZone('Europe/Brussels');

        return [
            'import event with one timestamp: date, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2017-05-21 00:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 23:59:00',
                                $timeZone
                            )
                        )
                    )
                ),
            ],
            'import event with one timestamp: date + timestart, no timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:00',
                            '10:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            )
                        )
                    )
                ),
            ],
            'import event with one timestamp: date + timestart + timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:00',
                            '11:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-21 11:00:00',
                                $timeZone
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps: dates, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-06-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-07-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 23:59:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-06-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-06-21 23:59:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-07-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-07-21 23:59:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps in non-chronological order: dates, no timestart or timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-07-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-06-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-07-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-07-21 23:59:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 23:59:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-06-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-06-21 23:59:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps: dates + timestart, no timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:00',
                            '10:00:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-06-21',
                            '10:00:00',
                            '10:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 10:00:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-06-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-06-21 10:00:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps: dates + timestart + timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:00',
                            '11:30:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-06-21',
                            '10:00:00',
                            '11:30:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 11:30:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-06-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-06-21 11:30:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps: mixed: dates, with or without timestart/timeend' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '00:00:00',
                            '23:59:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-06-21',
                            '10:00:00',
                            '10:00:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-07-21',
                            '10:00:00',
                            '11:30:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 00:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 23:59:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-06-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-06-21 10:00:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-07-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-07-21 11:30:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps with specific timeformat as one subevent' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-22',
                            '00:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-23',
                            '00:00:01',
                            '16:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2017-05-21 10:00:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2017-05-23 16:00:00',
                                $timeZone
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps with specific timeformat as multiple subevents' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-22',
                            '00:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-23',
                            '00:00:01',
                            '16:00:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-24',
                            '10:00:02',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-25',
                            '00:00:02',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-26',
                            '00:00:02',
                            '16:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-23 16:00:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-24 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-26 16:00:00',
                                    $timeZone
                                )
                            )
                        )
                    )
                ),
            ],
            'import event with multiple timestamps: mixed: dates with or without timestart/timeend and dates with specific timeformat imported as subevent' => [
                'cdbCalendar' => call_user_func(function () {
                    $cdbCalendar = new CultureFeed_Cdb_Data_Calendar_TimestampList();
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '10:00:00',
                            '16:00:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-21',
                            '20:00:00',
                            '01:00:00'
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-23',
                            '10:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-24',
                            '00:00:01',
                            null
                        )
                    );
                    $cdbCalendar->add(
                        new CultureFeed_Cdb_Data_Calendar_Timestamp(
                            '2017-05-25',
                            '00:00:01',
                            '16:00:00'
                        )
                    );
                    return $cdbCalendar;
                }),
                'expectedCalendar' => new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-21 16:00:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-21 20:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-22 01:00:00',
                                    $timeZone
                                )
                            )
                        ),
                        SubEvent::createAvailable(
                            new DateRange(
                                new DateTimeImmutable(
                                    '2017-05-23 10:00:00',
                                    $timeZone
                                ),
                                new DateTimeImmutable(
                                    '2017-05-25 16:00:00',
                                    $timeZone
                                )
                            )
                        )
                    )
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
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2011-11-11 11:11:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2011-11-11 12:12:12',
                                $timeZone
                            )
                        )
                    )
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
                'expectedCalendar' => new SingleSubEventCalendar(
                    SubEvent::createAvailable(
                        new DateRange(
                            new DateTimeImmutable(
                                '2011-11-11 11:11:00',
                                $timeZone
                            ),
                            new DateTimeImmutable(
                                '2011-11-11 11:11:11',
                                $timeZone
                            )
                        )
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider periodListDataProvider
     */
    public function it_creates_a_periodic_calendar_from_cdb_calendar_with_period_list(
        \CultureFeed_Cdb_Data_Calendar_PeriodList $cdbCalendar,
        Calendar $expectedCalendar
    ): void {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    private function createPeriodListFromXML(string $xmlContent): \CultureFeed_Cdb_Data_Calendar_PeriodList
    {
        $xmlElement = new \SimpleXMLElement($xmlContent);
        return \CultureFeed_Cdb_Data_Calendar_PeriodList::parseFromCdbXml($xmlElement);
    }

    public function periodListDataProvider(): array
    {
        $timeZone = new DateTimeZone('Europe/Brussels');

        return [
            'import event with period: datefrom + dateto, no weekscheme' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    SampleFiles::read(__DIR__ . '/samples/periodic/calendar_udb2_import_example_1101.xml')
                ),
                'expectedCalendar' => new PeriodicCalendar(
                    new DateRange(
                        new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                        new DateTimeImmutable('2017-12-31 00:00:00', $timeZone)
                    ),
                    new OpeningHours()
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme only openingtimes from' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    SampleFiles::read(__DIR__ . '/samples/periodic/calendar_udb2_import_example_1201.xml')
                ),
                'expectedCalendar' => new PeriodicCalendar(
                    new DateRange(
                        new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                        new DateTimeImmutable('2017-12-31 00:00:00', $timeZone)
                    ),
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(20), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(16), new Minute(0))
                        )
                    )
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme openingtimes from + to' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    SampleFiles::read(__DIR__ . '/samples/periodic/calendar_udb2_import_example_1301.xml')
                ),
                'expectedCalendar' => new PeriodicCalendar(
                    new DateRange(
                        new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                        new DateTimeImmutable('2017-12-31 00:00:00', $timeZone)
                    ),
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(22), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(20), new Minute(0))
                        )
                    )
                ),
            ],
            'import event with period: datefrom + dateto + weekscheme mix openingtimes from + to' => [
                'cdbCalendar' => $this->createPeriodListFromXML(
                    SampleFiles::read(__DIR__ . '/samples/periodic/calendar_udb2_import_example_1401.xml')
                ),
                'expectedCalendar' => new PeriodicCalendar(
                    new DateRange(
                        new DateTimeImmutable('2017-09-01 00:00:00', $timeZone),
                        new DateTimeImmutable('2017-12-31 00:00:00', $timeZone)
                    ),
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(22), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(16), new Minute(0))
                        )
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider permanentCalendarDataProvider
     */
    public function it_creates_a_permanent_calendar_from_cdb_calendar(
        \CultureFeed_Cdb_Data_Calendar_Permanent $cdbCalendar,
        Calendar $expectedCalendar
    ): void {
        $calendar = $this->factory->createFromCdbCalendar($cdbCalendar);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    private function createPermanentCalendarFromXML(string $xmlContent): \CultureFeed_Cdb_Data_Calendar_Permanent
    {
        $xmlElement = new \SimpleXMLElement($xmlContent);
        return \CultureFeed_Cdb_Data_Calendar_Permanent::parseFromCdbXml($xmlElement);
    }

    public function permanentCalendarDataProvider(): array
    {
        return [
            'import permanent event, no weekscheme as periodic event' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    SampleFiles::read(__DIR__ . '/samples/permanent/calendar_udb2_import_example_1501.xml')
                ),
                'expectedCalendar' => new PermanentCalendar(new OpeningHours()),
            ],
            'import permanent event with weekscheme only openingtimes from' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    SampleFiles::read(__DIR__ . '/samples/permanent/calendar_udb2_import_example_1601.xml')
                ),
                'expectedCalendar' => new PermanentCalendar(
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(20), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(16), new Minute(0))
                        )
                    )
                ),
            ],
            'import permanent event with weekscheme openingtimes from + to' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    SampleFiles::read(__DIR__ . '/samples/permanent/calendar_udb2_import_example_1701.xml')
                ),
                'expectedCalendar' => new PermanentCalendar(
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(22), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(20), new Minute(0))
                        )
                    )
                ),
            ],
            'import permanent event with weekscheme mix openingtimes from + to' => [
                'cdbCalendar' => $this->createPermanentCalendarFromXML(
                    SampleFiles::read(__DIR__ . '/samples/permanent/calendar_udb2_import_example_1801.xml')
                ),
                'expectedCalendar' => new PermanentCalendar(
                    new OpeningHours(
                        new OpeningHour(
                            new Days(
                                Day::monday(),
                                Day::thursday(),
                                Day::friday(),
                                Day::saturday()
                            ),
                            new Time(new Hour(20), new Minute(30)),
                            new Time(new Hour(22), new Minute(30))
                        ),
                        new OpeningHour(
                            new Days(
                                Day::sunday()
                            ),
                            new Time(new Hour(16), new Minute(0)),
                            new Time(new Hour(16), new Minute(0))
                        )
                    )
                ),
            ],
        ];
    }
}
