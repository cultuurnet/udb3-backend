<?php

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour as Udb3ModelHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute as Udb3ModelMinute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour as Udb3ModelOpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status as Udb3ModelStatus;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType as Udb3ModelStatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use DateTime;
use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class CalendarTest extends TestCase
{
    const START_DATE = '2016-03-06T10:00:00+01:00';
    const END_DATE = '2016-03-13T12:00:00+01:00';

    const TIMESTAMP_1 = '1457254800';
    const TIMESTAMP_1_START_DATE = '2016-03-06T10:00:00+01:00';
    const TIMESTAMP_1_END_DATE = '2016-03-06T10:00:00+01:00';
    const TIMESTAMP_2 = '1457859600';
    const TIMESTAMP_2_START_DATE = '2016-03-13T10:00:00+01:00';
    const TIMESTAMP_2_END_DATE = '2016-03-13T12:00:00+01:00';

    /**
     * @var Calendar
     */
    private $calendar;

    public function setUp()
    {
        $timestamp1 = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_1_START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_1_END_DATE)
        );

        $timestamp2 = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_2_START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_2_END_DATE)
        );

        $weekDays = (new DayOfWeekCollection())
            ->addDayOfWeek(DayOfWeek::MONDAY())
            ->addDayOfWeek(DayOfWeek::TUESDAY())
            ->addDayOfWeek(DayOfWeek::WEDNESDAY())
            ->addDayOfWeek(DayOfWeek::THURSDAY())
            ->addDayOfWeek(DayOfWeek::FRIDAY());

        $openingHour1 = new OpeningHour(
            new OpeningTime(new Hour(9), new Minute(0)),
            new OpeningTime(new Hour(12), new Minute(0)),
            $weekDays
        );

        $openingHour2 = new OpeningHour(
            new OpeningTime(new Hour(13), new Minute(0)),
            new OpeningTime(new Hour(17), new Minute(0)),
            $weekDays
        );

        $weekendDays = (new DayOfWeekCollection())
            ->addDayOfWeek(DayOfWeek::SATURDAY())
            ->addDayOfWeek(DayOfWeek::SUNDAY());

        $openingHour3 = new OpeningHour(
            new OpeningTime(new Hour(10), new Minute(0)),
            new OpeningTime(new Hour(16), new Minute(0)),
            $weekendDays
        );

        $this->calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            [
                self::TIMESTAMP_1 => $timestamp1,
                self::TIMESTAMP_2 => $timestamp2,
            ],
            [
                $openingHour1,
                $openingHour2,
                $openingHour3,
            ]
        );
    }

    /**
     * @test
     */
    public function time_stamps_need_to_have_type_time_stamp()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timestamps should have type TimeStamp.');

        new Calendar(
            CalendarType::SINGLE(),
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            [
                'wrong timestamp',
            ]
        );
    }

    /**
     * @test
     */
    public function opening_hours_need_to_have_type_opening_hour()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OpeningHours should have type OpeningHour.');

        new Calendar(
            CalendarType::PERIODIC(),
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            [],
            [
                'wrong opening hours',
            ]
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $this->assertEquals(
            [
                'type' => 'multiple',
                'startDate' => '2016-03-06T10:00:00+01:00',
                'endDate' => '2016-03-13T12:00:00+01:00',
                'status' => [
                    'type' => StatusType::available()->toNative(),
                ],
                'timestamps' => [
                    [
                        'startDate' => self::TIMESTAMP_1_START_DATE,
                        'endDate' => self::TIMESTAMP_1_END_DATE,
                        'status' => [
                            'type' => StatusType::available()->toNative(),
                        ],
                    ],
                    [
                        'startDate' => self::TIMESTAMP_2_START_DATE,
                        'endDate' => self::TIMESTAMP_2_END_DATE,
                        'status' => [
                            'type' => StatusType::available()->toNative(),
                        ],
                    ],
                ],
                'openingHours' => [
                    [
                        'opens' => '09:00',
                        'closes' => '12:00',
                        'dayOfWeek' => [
                            'monday',
                            'tuesday',
                            'wednesday',
                            'thursday',
                            'friday',
                        ],
                    ],
                    [
                        'opens' => '13:00',
                        'closes' => '17:00',
                        'dayOfWeek' => [
                            'monday',
                            'tuesday',
                            'wednesday',
                            'thursday',
                            'friday',
                        ],
                    ],
                    [
                        'opens' => '10:00',
                        'closes' => '16:00',
                        'dayOfWeek' => [
                            'saturday',
                            'sunday',
                        ],
                    ],
                ],
            ],
            $this->calendar->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $actualCalendar = Calendar::deserialize([
            'type' => 'multiple',
            'startDate' => '2016-03-06T10:00:00+01:00',
            'endDate' => '2016-03-13T12:00:00+01:00',
            'timestamps' => [
                [
                    'startDate' => self::TIMESTAMP_1_START_DATE,
                    'endDate' => self::TIMESTAMP_1_END_DATE,
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                ],
                [
                    'startDate' => self::TIMESTAMP_2_START_DATE,
                    'endDate' => self::TIMESTAMP_2_END_DATE,
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                ],
            ],
            'openingHours' => [
                [
                    'opens' => '09:00',
                    'closes' => '12:00',
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                    ],
                ],
                [
                    'opens' => '13:00',
                    'closes' => '17:00',
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                    ],
                ],
                [
                    'opens' => '10:00',
                    'closes' => '16:00',
                    'dayOfWeek' => [
                        'saturday',
                        'sunday',
                    ],
                ],
            ],
        ]);

        $this->assertEquals($this->calendar, $actualCalendar);
    }

    /**
     * @test
     */
    public function it_can_deserialize_with_explicit_status()
    {
        $status = new Status(
            StatusType::temporarilyUnavailable(),
            [
                new StatusReason(new Language('nl'), 'Jammer genoeg uitgesteld.'),
                new StatusReason(new Language('fr'), 'Malheureusement reporté.'),
            ]
        );

        $calendar = new Calendar(
            CalendarType::PERMANENT(),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00')
        );

        $this->assertEquals(
            $calendar->withStatus($status),
            Calendar::deserialize(
                [
                    'type' => 'permanent',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => [
                            'nl' => 'Jammer genoeg uitgesteld.',
                            'fr' => 'Malheureusement reporté.',
                        ],
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_without_overwriting_the_status_of_subEvents()
    {
        $timestamp1 = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_1_START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_1_END_DATE),
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Jammer genoeg geannuleerd.'),
                ]
            )
        );

        $timestamp2 = new Timestamp(
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_2_START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::TIMESTAMP_2_END_DATE),
            new Status(
                StatusType::temporarilyUnavailable(),
                [
                    new StatusReason(new Language('nl'), 'Jammer genoeg uitgesteld.'),
                ]
            )
        );

        $expected = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE),
            [
                self::TIMESTAMP_1 => $timestamp1,
                self::TIMESTAMP_2 => $timestamp2,
            ]
        );

        $actual = Calendar::deserialize($expected->serialize());

        $this->assertEquals($expected, $actual);
        $this->assertEquals(StatusType::temporarilyUnavailable(), $actual->getStatus()->getType());
        $this->assertEquals(StatusType::unavailable(), $actual->getTimestamps()[0]->getStatus()->getType());
        $this->assertEquals(StatusType::temporarilyUnavailable(), $actual->getTimestamps()[1]->getStatus()->getType());
    }

    /**
     * @test
     * @dataProvider jsonldCalendarProvider
     */
    public function it_should_generate_the_expected_json_for_a_calendar_of_each_type(
        Calendar $calendar,
        array $jsonld
    ) {
        $this->assertEquals($jsonld, $calendar->toJsonLd());
    }

    /**
     * @return array
     */
    public function jsonldCalendarProvider()
    {
        return [
            'single no sub event status' => [
                'calendar' => new Calendar(
                    CalendarType::SINGLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00')
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'single',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::available()->toNative(),
                            ],
                        ],
                    ],
                ],
            ],
            'single with sub event status postponed' => [
                'calendar' => new Calendar(
                    CalendarType::SINGLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::temporarilyUnavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Jammer genoeg uitgesteld.'),
                                    new StatusReason(new Language('fr'), 'Malheureusement reporté.'),
                                ]
                            )
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'single',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Jammer genoeg uitgesteld.',
                                    'fr' => 'Malheureusement reporté.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple no sub event status' => [
                'calendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-13T12:00:00+01:00')
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::available()->toNative(),
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::available()->toNative(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with single sub event scheduled' => [
                'calendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::temporarilyUnavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Jammer genoeg uitgesteld.'),
                                    new StatusReason(new Language('fr'), 'Malheureusement reporté.'),
                                ]
                            )
                        ),
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::available(),
                                [
                                    new StatusReason(new Language('nl'), 'Gelukkig gaat het door.'),
                                    new StatusReason(new Language('fr'), 'Heureusement, ça continue.'),
                                ]
                            )
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Jammer genoeg uitgesteld.',
                                    'fr' => 'Malheureusement reporté.',
                                ],
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::available()->toNative(),
                                'reason' => [
                                    'nl' => 'Gelukkig gaat het door.',
                                    'fr' => 'Heureusement, ça continue.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with single sub event postponed' => [
                'calendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::temporarilyUnavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Jammer genoeg uitgesteld.'),
                                    new StatusReason(new Language('fr'), 'Malheureusement reporté.'),
                                ]
                            )
                        ),
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Nog erger, het is afgelast.'),
                                    new StatusReason(new Language('fr'), 'Pire encore, il a été annulé.'),
                                ]
                            )
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => [
                                    'nl' => 'Jammer genoeg uitgesteld.',
                                    'fr' => 'Malheureusement reporté.',
                                ],
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::unavailable()->toNative(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with all sub events cancelled' => [
                'calendar' => new Calendar(
                    CalendarType::MULTIPLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Het is afgelast.'),
                                    new StatusReason(new Language('fr'), 'Il a été annulé.'),
                                ]
                            )
                        ),
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Nog erger, het is afgelast.'),
                                    new StatusReason(new Language('fr'), 'Pire encore, il a été annulé.'),
                                ]
                            )
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::unavailable()->toNative(),
                                'reason' => [
                                    'nl' => 'Het is afgelast.',
                                    'fr' => 'Il a été annulé.',
                                ],
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::unavailable()->toNative(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with all sub events cancelled but an incorrect top status which should get corrected' => [
                'calendar' => (new Calendar(
                    CalendarType::MULTIPLE(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Het is afgelast.'),
                                    new StatusReason(new Language('fr'), 'Il a été annulé.'),
                                ]
                            )
                        ),
                        new Timestamp(
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-06T10:00:00+01:00'),
                            DateTime::createFromFormat(DateTime::ATOM, '2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(new Language('nl'), 'Nog erger, het is afgelast.'),
                                    new StatusReason(new Language('fr'), 'Pire encore, il a été annulé.'),
                                ]
                            )
                        ),
                    ]
                ))->withStatus(
                    new Status(
                        StatusType::available(),
                        [
                            new StatusReason(new Language('nl'), 'Alles goed'),
                            new StatusReason(new Language('en'), 'All good'),
                        ]
                    )
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::unavailable()->toNative(),
                                'reason' => [
                                    'nl' => 'Het is afgelast.',
                                    'fr' => 'Il a été annulé.',
                                ],
                            ],
                        ],
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::unavailable()->toNative(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'periodic' => [
                'calendar' => new Calendar(
                    CalendarType::PERIODIC(),
                    DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
                    DateTime::createFromFormat(DateTime::ATOM, self::END_DATE)
                ),
                'jsonld' => [
                    'calendarType' => 'periodic',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                ],
            ],
            'permanent' => [
                'calendar' => new Calendar(
                    CalendarType::PERMANENT()
                ),
                'jsonld' => [
                    'calendarType' => 'permanent',
                    'status' => [
                        'type' => StatusType::available()->toNative(),
                    ],
                ],
            ],
            'permanent_with_changed_status_and_reason' => [
                'calendar' => (new Calendar(
                    CalendarType::PERMANENT()
                ))->withStatus(
                    new Status(
                        StatusType::temporarilyUnavailable(),
                        [
                            new StatusReason(new Language('nl'), 'We zijn in volle verbouwing'),
                        ]
                    )
                ),
                'jsonld' => [
                    'calendarType' => 'permanent',
                    'status' => [
                        'type' => StatusType::temporarilyUnavailable()->toNative(),
                        'reason' => [
                            'nl' => 'We zijn in volle verbouwing',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_assume_the_timezone_is_Brussels_when_none_is_provided_when_deserializing()
    {
        $oldCalendarData = [
            'type' => 'periodic',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expectedCalendar = new Calendar(
            CalendarType::PERIODIC(),
            DateTime::createFromFormat(DateTime::ATOM, self::START_DATE),
            DateTime::createFromFormat(DateTime::ATOM, self::END_DATE)
        );

        $calendar = Calendar::deserialize($oldCalendarData);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_start_date_can_not_be_converted()
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06 10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $this->expectException(\InvalidArgumentException::class);

        Calendar::deserialize($invalidCalendarData);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_end_date_can_not_be_converted()
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13 12:00:00',
            'timestamps' => [],
        ];

        $this->expectException(\InvalidArgumentException::class);

        Calendar::deserialize($invalidCalendarData);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_and_end_date_if_one_is_missing_for_single_calendars()
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::SINGLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_date_if_one_is_missing_for_single_calendars()
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => null,
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::SINGLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
            null,
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_and_end_date_if_one_is_missing_for_multiple_calendars()
    {
        $invalidCalendarData = [
            'type' => 'multiple',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-13T12:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_date_if_one_is_missing_for_multiple_calendars()
    {
        $invalidCalendarData = [
            'type' => 'multiple',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => null,
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
            null,
            [
                new Timestamp(
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00'),
                    DateTime::createFromFormat(DateTime::ATOM, '2016-03-06T10:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @dataProvider periodicCalendarWithMissingDatesDataProvider
     */
    public function it_should_not_create_a_periodic_calendar_with_missing_dates(array $calendarData)
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('A period should have a start- and end-date.');

        Calendar::deserialize($calendarData);
    }

    public function periodicCalendarWithMissingDatesDataProvider()
    {
        return [
            'no dates' => [
                'calendarData' => [
                    'type' => 'periodic',
                ],
            ],
            'start date missing' => [
                'calendarData' => [
                    'type' => 'periodic',
                    'endDate' => '2016-03-13T12:00:00',
                ],
            ],
            'end date missing' => [
                'calendarData' => [
                    'type' => 'periodic',
                    'startDate' => '2016-03-06T10:00:00',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_single_date_range_calendar()
    {
        $subEvent = new SubEvent(
            new DateRange(
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
            ),
            new Udb3ModelStatus(Udb3ModelStatusType::Available())
        );

        $udb3ModelCalendar = new SingleSubEventCalendar($subEvent);

        $expected = new Calendar(
            CalendarType::SINGLE(),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00'),
            [
                new Timestamp(
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
                ),
            ],
            []
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_multiple_date_range_calendar()
    {
        $subEvents = new SubEvents(
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
                ),
                new Udb3ModelStatus(Udb3ModelStatusType::Available())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-09T10:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-10T10:00:00+01:00')
                ),
                new Udb3ModelStatus(Udb3ModelStatusType::Available())
            )
        );

        $udb3ModelCalendar = new MultipleSubEventsCalendar($subEvents);

        $expected = new Calendar(
            CalendarType::MULTIPLE(),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-10T10:00:00+01:00'),
            [
                new Timestamp(
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
                ),
                new Timestamp(
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-09T10:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-10T10:00:00+01:00')
                ),
            ],
            []
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_periodic_calendar()
    {
        $dateRange = new DateRange(
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
        );

        $openingHours = new OpeningHours();

        $udb3ModelCalendar = new PeriodicCalendar($dateRange, $openingHours);

        $expected = new Calendar(
            CalendarType::PERIODIC(),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00'),
            [],
            []
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_periodic_calendar_with_opening_hours()
    {
        $dateRange = new DateRange(
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00')
        );

        $openingHours = new OpeningHours(
            new Udb3ModelOpeningHour(
                new Days(
                    Day::monday(),
                    Day::tuesday()
                ),
                new Time(
                    new Udb3ModelHour(8),
                    new Udb3ModelMinute(0)
                ),
                new Time(
                    new Udb3ModelHour(12),
                    new Udb3ModelMinute(59)
                )
            ),
            new Udb3ModelOpeningHour(
                new Days(
                    Day::saturday()
                ),
                new Time(
                    new Udb3ModelHour(10),
                    new Udb3ModelMinute(0)
                ),
                new Time(
                    new Udb3ModelHour(14),
                    new Udb3ModelMinute(0)
                )
            )
        );

        $udb3ModelCalendar = new PeriodicCalendar($dateRange, $openingHours);

        $expected = new Calendar(
            CalendarType::PERIODIC(),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-06T10:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2016-03-07T10:00:00+01:00'),
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(8), new Minute(0)),
                    new OpeningTime(new Hour(12), new Minute(59)),
                    new DayOfWeekCollection(
                        DayOfWeek::MONDAY(),
                        DayOfWeek::TUESDAY()
                    )
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(10), new Minute(0)),
                    new OpeningTime(new Hour(14), new Minute(0)),
                    new DayOfWeekCollection(
                        DayOfWeek::SATURDAY()
                    )
                ),
            ]
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_permanent_calendar()
    {
        $openingHours = new OpeningHours();
        $udb3ModelCalendar = new PermanentCalendar($openingHours);

        $expected = new Calendar(
            CalendarType::PERMANENT(),
            null,
            null,
            [],
            []
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_permanent_calendar_with_opening_hours()
    {
        $openingHours = new OpeningHours(
            new Udb3ModelOpeningHour(
                new Days(
                    Day::monday(),
                    Day::tuesday()
                ),
                new Time(
                    new Udb3ModelHour(8),
                    new Udb3ModelMinute(0)
                ),
                new Time(
                    new Udb3ModelHour(12),
                    new Udb3ModelMinute(59)
                )
            ),
            new Udb3ModelOpeningHour(
                new Days(
                    Day::saturday()
                ),
                new Time(
                    new Udb3ModelHour(10),
                    new Udb3ModelMinute(0)
                ),
                new Time(
                    new Udb3ModelHour(14),
                    new Udb3ModelMinute(0)
                )
            )
        );

        $udb3ModelCalendar = new PermanentCalendar($openingHours);

        $expected = new Calendar(
            CalendarType::PERMANENT(),
            null,
            null,
            [],
            [
                new OpeningHour(
                    new OpeningTime(new Hour(8), new Minute(0)),
                    new OpeningTime(new Hour(12), new Minute(59)),
                    new DayOfWeekCollection(
                        DayOfWeek::MONDAY(),
                        DayOfWeek::TUESDAY()
                    )
                ),
                new OpeningHour(
                    new OpeningTime(new Hour(10), new Minute(0)),
                    new OpeningTime(new Hour(14), new Minute(0)),
                    new DayOfWeekCollection(
                        DayOfWeek::SATURDAY()
                    )
                ),
            ]
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_takes_into_account_udb3_model_calendar_status()
    {
        $udb3ModelCalendar = (new PermanentCalendar(new OpeningHours()))
            ->withStatus(new Udb3ModelStatus(Udb3ModelStatusType::TemporarilyUnavailable()));

        $expected = (new Calendar(CalendarType::PERMANENT()))
            ->withStatus(new Status(StatusType::temporarilyUnavailable(), []));

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_determine_same_calendars()
    {
        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $sameCalendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T12:12:12+01:00')
        );

        $otherCalendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-27T11:11:11+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-28T12:12:12+01:00')
        );

        $this->assertTrue($calendar->sameAs($sameCalendar));
        $this->assertFalse($calendar->sameAs($otherCalendar));
    }

    /**
     * @test
     */
    public function it_should_return_timestamps_in_chronological_order(): void
    {
        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-30T12:12:12+01:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-05T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-10T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-07T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-09T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-15T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-25T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-20T12:12:12+01:00')
                ),
            ]
        );

        $expected = [
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-20T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-05T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-10T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-07T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-09T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-15T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-25T12:12:12+01:00')
            ),
        ];

        $this->assertEquals(
            $expected,
            $calendar->getTimestamps()
        );
    }

    /**
     * @test
     */
    public function it_can_change_top_status(): void
    {
        $timestamps = [
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-20T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-05T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-10T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-07T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-09T12:12:12+01:00')
            ),
            new Timestamp(
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-15T11:11:11+01:00'),
                DateTime::createFromFormat(\DateTime::ATOM, '2020-04-25T12:12:12+01:00')
            ),
        ];

        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-30T12:12:12+01:00'),
            $timestamps
        );

        $this->assertEquals(
            new Status(StatusType::available(), []),
            $calendar->getStatus()
        );

        $newStatus = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het mag niet van de afgevaardigde van de eerste minister'),
            ]
        );

        $updatedCalendar = $calendar->withStatus($newStatus);

        $this->assertEquals($newStatus, $updatedCalendar->getStatus());
        $this->assertEquals($timestamps, $updatedCalendar->getTimestamps());
    }

    /**
     * @test
     */
    public function it_can_change_top_status_and_timestamp_statuses(): void
    {
        $calendar = new Calendar(
            CalendarType::MULTIPLE(),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
            DateTime::createFromFormat(\DateTime::ATOM, '2020-04-30T12:12:12+01:00'),
            [
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-05T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-10T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-07T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-09T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-15T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-25T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-01T11:11:11+01:00'),
                    DateTime::createFromFormat(\DateTime::ATOM, '2020-04-20T12:12:12+01:00')
                ),
            ]
        );

        $this->assertEquals(
            new Status(StatusType::available(), []),
            $calendar->getStatus()
        );

        $newStatus = new Status(
            StatusType::unavailable(),
            [
                new StatusReason(new Language('nl'), 'Het mag niet van de afgevaardigde van de eerste minister'),
            ]
        );

        $updatedCalendar = $calendar
            ->withStatus($newStatus)
            ->withStatusOnTimestamps($newStatus);

        $this->assertEquals($newStatus, $updatedCalendar->getStatus());

        foreach ($updatedCalendar->getTimestamps() as $timestamp) {
            $this->assertEquals($newStatus, $timestamp->getStatus());
        }
    }
}
