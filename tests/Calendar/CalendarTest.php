<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType as Udb3ModelStatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use DateTime;
use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase
{
    public const START_DATE = '2016-03-06T10:00:00+01:00';
    public const END_DATE = '2016-03-13T12:00:00+01:00';
    public const TIMESTAMP_1 = '1457254800';
    public const TIMESTAMP_1_START_DATE = '2016-03-06T10:00:00+01:00';
    public const TIMESTAMP_1_END_DATE = '2016-03-06T10:00:00+01:00';
    public const TIMESTAMP_2 = '1457859600';
    public const TIMESTAMP_2_START_DATE = '2016-03-13T10:00:00+01:00';
    public const TIMESTAMP_2_END_DATE = '2016-03-13T12:00:00+01:00';

    private Calendar $calendar;

    public function setUp(): void
    {
        $timestamp1 = new Timestamp(
            DateTimeFactory::fromAtom(self::TIMESTAMP_1_START_DATE),
            DateTimeFactory::fromAtom(self::TIMESTAMP_1_END_DATE)
        );

        $timestamp2 = new Timestamp(
            DateTimeFactory::fromAtom(self::TIMESTAMP_2_START_DATE),
            DateTimeFactory::fromAtom(self::TIMESTAMP_2_END_DATE)
        );

        $weekDays = (new Days())
            ->with(Day::monday())
            ->with(Day::tuesday())
            ->with(Day::wednesday())
            ->with(Day::thursday())
            ->with(Day::friday());

        $openingHour1 = new OpeningHour(
            $weekDays,
            new Time(new Hour(9), new Minute(0)),
            new Time(new Hour(12), new Minute(0))
        );

        $openingHour2 = new OpeningHour(
            $weekDays,
            new Time(new Hour(13), new Minute(0)),
            new Time(new Hour(17), new Minute(0))
        );

        $weekendDays = (new Days())
            ->with(Day::saturday())
            ->with(Day::sunday());

        $openingHour3 = new OpeningHour(
            $weekendDays,
            new Time(new Hour(10), new Minute(0)),
            new Time(new Hour(16), new Minute(0))
        );

        $this->calendar = new Calendar(
            CalendarType::multiple(),
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
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
     * @dataProvider calendarProvider
     */
    public function it_determines_booking_availability_from_sub_events(
        Calendar $calendar,
        BookingAvailability $expectedBookingAvailability
    ): void {
        $this->assertEquals($expectedBookingAvailability, $calendar->getBookingAvailability());
    }

    public function calendarProvider(): array
    {
        return [
            'single available' => [
                new Calendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Available()
                        ),
                    ]
                ),
                BookingAvailability::Available(),
            ],
            'single unavailable' => [
                new Calendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                    ]
                ),
                BookingAvailability::Unavailable(),
            ],
            'multiple available' => [
                new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Available()
                        ),
                    ]
                ),
                BookingAvailability::Available(),
            ],
            'multiple unavailable' => [
                new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                    ]
                ),
                BookingAvailability::Unavailable(),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_allows_updating_booking_availability_on_single_type(): void
    {
        $singleCalendar = new Calendar(
            CalendarType::single(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00'),
                    DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00')
                ),
            ]
        );

        $singleCalendar = $singleCalendar->withBookingAvailability(BookingAvailability::Unavailable());

        $this->assertEquals(BookingAvailability::Unavailable(), $singleCalendar->getBookingAvailability());
    }

    /**
     * @test
     */
    public function it_allows_updating_booking_availability_on_multiple_type(): void
    {
        $multipleCalendar = new Calendar(
            CalendarType::multiple(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00')
                ),
            ]
        );

        $multipleCalendar = $multipleCalendar->withBookingAvailability(BookingAvailability::Unavailable());

        $this->assertEquals(BookingAvailability::Unavailable(), $multipleCalendar->getBookingAvailability());
    }

    /**
     * @test
     */
    public function it_prevents_updating_booking_availability_on_permanent_type(): void
    {
        $permanentCalendar = new Calendar(CalendarType::permanent());

        $this->expectException(CalendarTypeNotSupported::class);

        $permanentCalendar->withBookingAvailability(BookingAvailability::Unavailable());
    }

    /**
     * @test
     */
    public function it_prevents_updating_booking_availability_on_periodic_type(): void
    {
        $periodicCalendar = new Calendar(
            CalendarType::periodic(),
            new DateTime('2021-03-18T14:00:00+01:00'),
            new DateTime('2021-03-18T14:00:00+01:00')
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $periodicCalendar->withBookingAvailability(BookingAvailability::Unavailable());
    }

    /**
     * @test
     */
    public function it_allows_updating_booking_availability_on_timestamp_of_single_type(): void
    {
        $singleCalendar = new Calendar(
            CalendarType::single(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00'),
                    DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00')
                ),
            ]
        );

        $singleCalendar = $singleCalendar->withBookingAvailabilityOnTimestamps(BookingAvailability::Unavailable());

        $this->assertEquals(
            BookingAvailability::Unavailable(),
            $singleCalendar->getTimestamps()[0]->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_allows_updating_booking_availability_on_timestamps_of_multiple_type(): void
    {
        $multipleCalendar = new Calendar(
            CalendarType::multiple(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00')
                ),
            ]
        );

        $multipleCalendar = $multipleCalendar->withBookingAvailabilityOnTimestamps(BookingAvailability::Unavailable());

        $this->assertEquals(
            BookingAvailability::Unavailable(),
            $multipleCalendar->getTimestamps()[0]->getBookingAvailability()
        );
        $this->assertEquals(
            BookingAvailability::Unavailable(),
            $multipleCalendar->getTimestamps()[1]->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_prevents_updating_booking_availability_on_timestamps_of_permanent_type(): void
    {
        $permanentCalendar = new Calendar(CalendarType::permanent());

        $this->expectException(CalendarTypeNotSupported::class);

        $permanentCalendar->withBookingAvailabilityOnTimestamps(BookingAvailability::Unavailable());
    }

    /**
     * @test
     */
    public function it_prevents_updating_booking_availability_on_timestamps_of_periodic_type(): void
    {
        $periodicCalendar = new Calendar(
            CalendarType::periodic(),
            new DateTime('2021-03-18T14:00:00+01:00'),
            new DateTime('2021-03-18T14:00:00+01:00')
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $periodicCalendar->withBookingAvailabilityOnTimestamps(BookingAvailability::Unavailable());
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $this->assertEquals(
            [
                'type' => 'multiple',
                'startDate' => '2016-03-06T10:00:00+01:00',
                'endDate' => '2016-03-13T12:00:00+01:00',
                'status' => [
                    'type' => StatusType::Available()->toString(),
                ],
                'bookingAvailability' => [
                    'type' => BookingAvailabilityType::Available()->toString(),
                ],
                'timestamps' => [
                    [
                        'startDate' => self::TIMESTAMP_1_START_DATE,
                        'endDate' => self::TIMESTAMP_1_END_DATE,
                        'status' => [
                            'type' => StatusType::Available()->toString(),
                        ],
                        'bookingAvailability' => [
                            'type' => BookingAvailabilityType::Available()->toString(),
                        ],
                    ],
                    [
                        'startDate' => self::TIMESTAMP_2_START_DATE,
                        'endDate' => self::TIMESTAMP_2_END_DATE,
                        'status' => [
                            'type' => StatusType::Available()->toString(),
                        ],
                        'bookingAvailability' => [
                            'type' => BookingAvailabilityType::Available()->toString(),
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
    public function it_can_deserialize(): void
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
                        'type' => StatusType::Available()->toString(),
                    ],
                ],
                [
                    'startDate' => self::TIMESTAMP_2_START_DATE,
                    'endDate' => self::TIMESTAMP_2_END_DATE,
                    'status' => [
                        'type' => StatusType::Available()->toString(),
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
    public function it_can_deserialize_with_explicit_status(): void
    {
        $status = new Status(
            StatusType::TemporarilyUnavailable(),
            (new TranslatedStatusReason(
                new Language('nl'),
                new StatusReason('Jammer genoeg uitgesteld.')
            ))->withTranslation(
                new Language('fr'),
                new StatusReason('Malheureusement reporté.')
            )
        );

        $calendar = new Calendar(
            CalendarType::permanent(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
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
    public function it_can_deserialize_with_explicit_booking_availability(): void
    {
        $calendar = new Calendar(
            CalendarType::single(),
            new DateTime('2021-03-18T14:00:00+01:00'),
            new DateTime('2021-03-18T16:00:00+01:00'),
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00'),
                    DateTimeFactory::fromAtom('2021-03-18T16:00:00+01:00'),
                    null,
                    BookingAvailability::Unavailable()
                ),
            ]
        );

        $this->assertEquals(
            $calendar->withBookingAvailability(BookingAvailability::Unavailable()),
            Calendar::deserialize(
                [
                    'type' => 'single',
                    'startDate' => '2021-03-18T14:00:00+01:00',
                    'endDate' => '2021-03-18T16:00:00+01:00',
                    'bookingAvailability' => [
                        'type' => 'Unavailable',
                    ],
                    'timestamps' => [
                        [
                            'startDate' => '2021-03-18T14:00:00+01:00',
                            'endDate' => '2021-03-18T16:00:00+01:00',
                            'bookingAvailability' => [
                                'type' => 'Unavailable',
                            ],
                        ],
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_without_overwriting_the_status_of_subEvents(): void
    {
        $timestamp1 = new Timestamp(
            DateTimeFactory::fromAtom(self::TIMESTAMP_1_START_DATE),
            DateTimeFactory::fromAtom(self::TIMESTAMP_1_END_DATE),
            new Status(
                StatusType::Unavailable(),
                new TranslatedStatusReason(
                    new Language('nl'),
                    new StatusReason('Jammer genoeg geannuleerd.')
                )
            )
        );

        $timestamp2 = new Timestamp(
            DateTimeFactory::fromAtom(self::TIMESTAMP_2_START_DATE),
            DateTimeFactory::fromAtom(self::TIMESTAMP_2_END_DATE),
            new Status(
                StatusType::TemporarilyUnavailable(),
                new TranslatedStatusReason(
                    new Language('nl'),
                    new StatusReason('Jammer genoeg geannuleerd.')
                )
            )
        );

        $expected = new Calendar(
            CalendarType::multiple(),
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            [
                self::TIMESTAMP_1 => $timestamp1,
                self::TIMESTAMP_2 => $timestamp2,
            ]
        );

        $actual = Calendar::deserialize($expected->serialize());

        $this->assertEquals($expected, $actual);
        $this->assertEquals(StatusType::TemporarilyUnavailable(), $actual->getStatus()->getType());
        $this->assertEquals(StatusType::Unavailable(), $actual->getTimestamps()[0]->getStatus()->getType());
        $this->assertEquals(StatusType::TemporarilyUnavailable(), $actual->getTimestamps()[1]->getStatus()->getType());
    }

    /**
     * @test
     */
    public function it_handles_incorrect_start_and_end_date(): void
    {
        $calendar = [
            'type' => 'single',
            'startDate' => '2021-03-18T14:00:00+01:00',
            'endDate' => '2021-03-18T10:00:00+01:00',
            'timestamps' => [
                [
                    'startDate' => '2021-03-18T14:00:00+01:00',
                    'endDate' => '2021-03-18T10:00:00+01:00',
                ],
            ],
        ];

        $this->assertEquals(
            new Calendar(
                CalendarType::single(),
                new DateTime('2021-03-18T14:00:00+01:00'),
                new DateTime('2021-03-18T14:00:00+01:00'),
                [
                    new Timestamp(
                        DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00'),
                        DateTimeFactory::fromAtom('2021-03-18T14:00:00+01:00')
                    ),
                ]
            ),
            Calendar::deserialize($calendar)
        );
    }

    /**
     * @test
     * @dataProvider jsonldCalendarProvider
     */
    public function it_should_generate_the_expected_json_for_a_calendar_of_each_type(
        Calendar $calendar,
        array $jsonld
    ): void {
        $this->assertEquals($jsonld, $calendar->toJsonLd());
    }

    public function jsonldCalendarProvider(): array
    {
        return [
            'single no sub event status' => [
                'calendar' => new Calendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'single',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'single with sub event status postponed' => [
                'calendar' => new Calendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Jammer genoeg uitgesteld.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Malheureusement reporté.'))
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
                        'reason' => [
                            'nl' => 'Jammer genoeg uitgesteld.',
                            'fr' => 'Malheureusement reporté.',
                        ],
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
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
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple no sub event status' => [
                'calendar' => new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00')
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with single sub event scheduled' => [
                'calendar' => new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Jammer genoeg uitgesteld.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Malheureusement reporté.'))
                            )
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Available(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Gelukkig gaat het door.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Heureusement, ça continue.'))
                            )
                        ),
                    ]
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
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
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                                'reason' => [
                                    'nl' => 'Gelukkig gaat het door.',
                                    'fr' => 'Heureusement, ça continue.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with single sub event postponed' => [
                'calendar' => new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Jammer genoeg uitgesteld.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Malheureusement reporté.'))
                            )
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Unavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Nog erger, het is afgelast.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Pire encore, il a été annulé.'))
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
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
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
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Unavailable()->toString(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with all sub events cancelled' => [
                'calendar' => new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Unavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Het is afgelast.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Il a été annulé.'))
                            )
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Unavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Nog erger, het is afgelast.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Pire encore, il a été annulé.'))
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
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Unavailable()->toString(),
                                'reason' => [
                                    'nl' => 'Het is afgelast.',
                                    'fr' => 'Il a été annulé.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Unavailable()->toString(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with all sub events cancelled but an incorrect top status which should get corrected' => [
                'calendar' => (new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Unavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Het is afgelast.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Il a été annulé.'))
                            )
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            new Status(
                                StatusType::Unavailable(),
                                (new TranslatedStatusReason(new Language('nl'), new StatusReason('Nog erger, het is afgelast.')))
                                    ->withTranslation(new Language('fr'), new StatusReason('Pire encore, il a été annulé.'))
                            )
                        ),
                    ]
                ))->withStatus(
                    new Status(
                        StatusType::Available(),
                        (new TranslatedStatusReason(new Language('nl'), new StatusReason('Alles goed')))
                            ->withTranslation(new Language('fr'), new StatusReason('All good'))
                    )
                ),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Unavailable()->toString(),
                                'reason' => [
                                    'nl' => 'Het is afgelast.',
                                    'fr' => 'Il a été annulé.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Unavailable()->toString(),
                                'reason' => [
                                    'nl' => 'Nog erger, het is afgelast.',
                                    'fr' => 'Pire encore, il a été annulé.',
                                ],
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Available()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'multiple with corrected booking availability' => [
                'calendar' => (new Calendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        new Timestamp(
                            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                        new Timestamp(
                            DateTimeFactory::fromAtom('2020-03-06T10:00:00+01:00'),
                            DateTimeFactory::fromAtom('2020-03-13T12:00:00+01:00'),
                            null,
                            BookingAvailability::Unavailable()
                        ),
                    ]
                ))->withBookingAvailability(BookingAvailability::Available()),
                'jsonld' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2020-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Unavailable()->toString(),
                    ],
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2016-03-06T10:00:00+01:00',
                            'endDate' => '2016-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Unavailable()->toString(),
                            ],
                        ],
                        [
                            'id' => 1,
                            '@type' => 'Event',
                            'startDate' => '2020-03-06T10:00:00+01:00',
                            'endDate' => '2020-03-13T12:00:00+01:00',
                            'status' => [
                                'type' => StatusType::Available()->toString(),
                            ],
                            'bookingAvailability' => [
                                'type' => BookingAvailabilityType::Unavailable()->toString(),
                            ],
                        ],
                    ],
                ],
            ],
            'periodic' => [
                'calendar' => new Calendar(
                    CalendarType::periodic(),
                    DateTimeFactory::fromAtom(self::START_DATE),
                    DateTimeFactory::fromAtom(self::END_DATE)
                ),
                'jsonld' => [
                    'calendarType' => 'periodic',
                    'startDate' => '2016-03-06T10:00:00+01:00',
                    'endDate' => '2016-03-13T12:00:00+01:00',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                ],
            ],
            'permanent' => [
                'calendar' => new Calendar(
                    CalendarType::permanent()
                ),
                'jsonld' => [
                    'calendarType' => 'permanent',
                    'status' => [
                        'type' => StatusType::Available()->toString(),
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                ],
            ],
            'permanent_with_changed_status_and_reason' => [
                'calendar' => (new Calendar(
                    CalendarType::permanent()
                ))->withStatus(
                    new Status(
                        StatusType::TemporarilyUnavailable(),
                        new TranslatedStatusReason(new Language('nl'), new StatusReason('We zijn in volle verbouwing'))
                    )
                ),
                'jsonld' => [
                    'calendarType' => 'permanent',
                    'status' => [
                        'type' => StatusType::TemporarilyUnavailable()->toString(),
                        'reason' => [
                            'nl' => 'We zijn in volle verbouwing',
                        ],
                    ],
                    'bookingAvailability' => [
                        'type' => BookingAvailabilityType::Available()->toString(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_assume_the_timezone_is_Brussels_when_none_is_provided_when_deserializing(): void
    {
        $oldCalendarData = [
            'type' => 'periodic',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expectedCalendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE)
        );

        $calendar = Calendar::deserialize($oldCalendarData);

        $this->assertEquals($expectedCalendar, $calendar);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_start_date_can_not_be_converted(): void
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
    public function it_throws_invalid_argument_exception_when_end_date_can_not_be_converted(): void
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
    public function it_adds_a_timestamp_based_on_start_and_end_date_if_one_is_missing_for_single_calendars(): void
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::single(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_date_if_one_is_missing_for_single_calendars(): void
    {
        $invalidCalendarData = [
            'type' => 'single',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => null,
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::single(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_and_end_date_if_one_is_missing_for_multiple_calendars(): void
    {
        $invalidCalendarData = [
            'type' => 'multiple',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => '2016-03-13T12:00:00',
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::multiple(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00'),
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-13T12:00:00+01:00')
                ),
            ]
        );

        $actual = Calendar::deserialize($invalidCalendarData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_adds_a_timestamp_based_on_start_date_if_one_is_missing_for_multiple_calendars(): void
    {
        $invalidCalendarData = [
            'type' => 'multiple',
            'startDate' => '2016-03-06T10:00:00',
            'endDate' => null,
            'timestamps' => [],
        ];

        $expected = new Calendar(
            CalendarType::multiple(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00')
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
    public function it_should_not_create_a_periodic_calendar_with_missing_dates(array $calendarData): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('A period should have a start- and end-date.');

        Calendar::deserialize($calendarData);
    }

    public function periodicCalendarWithMissingDatesDataProvider(): array
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
    public function it_should_be_creatable_from_an_udb3_model_single_date_range_calendar(): void
    {
        $subEvent = new SubEvent(
            new DateRange(
                DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00')
            ),
            new Status(Udb3ModelStatusType::Unavailable()),
            new BookingAvailability(BookingAvailabilityType::Unavailable())
        );

        $udb3ModelCalendar = (new SingleSubEventCalendar($subEvent))
            ->withStatus(new Status(Udb3ModelStatusType::Unavailable()))
            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $expected = (new Calendar(
            CalendarType::single(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00'),
                    new Status(StatusType::Unavailable(), null),
                    BookingAvailability::Unavailable()
                ),
            ],
            []
        ))->withStatus(new Status(StatusType::Unavailable(), null))
            ->withBookingAvailability(BookingAvailability::Unavailable());

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_multiple_date_range_calendar(): void
    {
        $subEvents = new SubEvents(
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00')
                ),
                new Status(Udb3ModelStatusType::Unavailable()),
                new BookingAvailability(BookingAvailabilityType::Unavailable())
            ),
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromAtom('2016-03-09T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-10T10:00:00+01:00')
                ),
                new Status(Udb3ModelStatusType::Unavailable()),
                new BookingAvailability(BookingAvailabilityType::Unavailable())
            )
        );

        $udb3ModelCalendar = (new MultipleSubEventsCalendar($subEvents))
            ->withStatus(new Status(Udb3ModelStatusType::Unavailable()))
            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $expected = (new Calendar(
            CalendarType::multiple(),
            null,
            null,
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00'),
                    new Status(StatusType::Unavailable(), null),
                    BookingAvailability::Unavailable()
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2016-03-09T10:00:00+01:00'),
                    DateTimeFactory::fromAtom('2016-03-10T10:00:00+01:00'),
                    new Status(StatusType::Unavailable(), null),
                    BookingAvailability::Unavailable()
                ),
            ],
            []
        ))->withStatus(new Status(StatusType::Unavailable(), null))
            ->withBookingAvailability(BookingAvailability::Unavailable());

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_periodic_calendar(): void
    {
        $dateRange = new DateRange(
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00')
        );

        $openingHours = new OpeningHours();

        $udb3ModelCalendar = new PeriodicCalendar($dateRange, $openingHours);

        $expected = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00'),
            [],
            []
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_periodic_calendar_with_opening_hours(): void
    {
        $dateRange = new DateRange(
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00')
        );

        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(
                    Day::monday(),
                    Day::tuesday()
                ),
                new Time(
                    new Hour(8),
                    new Minute(0)
                ),
                new Time(
                    new Hour(12),
                    new Minute(59)
                )
            ),
            new OpeningHour(
                new Days(
                    Day::saturday()
                ),
                new Time(
                    new Hour(10),
                    new Minute(0)
                ),
                new Time(
                    new Hour(14),
                    new Minute(0)
                )
            )
        );

        $udb3ModelCalendar = new PeriodicCalendar($dateRange, $openingHours);

        $expected = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2016-03-06T10:00:00+01:00'),
            DateTimeFactory::fromAtom('2016-03-07T10:00:00+01:00'),
            [],
            [
                new OpeningHour(
                    new Days(
                        Day::monday(),
                        Day::tuesday()
                    ),
                    new Time(new Hour(8), new Minute(0)),
                    new Time(new Hour(12), new Minute(59))
                ),
                new OpeningHour(
                    new Days(
                        Day::saturday()
                    ),
                    new Time(new Hour(10), new Minute(0)),
                    new Time(new Hour(14), new Minute(0))
                ),
            ]
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_an_udb3_model_permanent_calendar(): void
    {
        $openingHours = new OpeningHours();
        $udb3ModelCalendar = new PermanentCalendar($openingHours);

        $expected = new Calendar(
            CalendarType::permanent(),
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
    public function it_should_be_creatable_from_an_udb3_model_permanent_calendar_with_opening_hours(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(
                new Days(
                    Day::monday(),
                    Day::tuesday()
                ),
                new Time(
                    new Hour(8),
                    new Minute(0)
                ),
                new Time(
                    new Hour(12),
                    new Minute(59)
                )
            ),
            new OpeningHour(
                new Days(
                    Day::saturday()
                ),
                new Time(
                    new Hour(10),
                    new Minute(0)
                ),
                new Time(
                    new Hour(14),
                    new Minute(0)
                )
            )
        );

        $udb3ModelCalendar = new PermanentCalendar($openingHours);

        $expected = new Calendar(
            CalendarType::permanent(),
            null,
            null,
            [],
            [
                new OpeningHour(
                    new Days(
                        Day::monday(),
                        Day::tuesday()
                    ),
                    new Time(new Hour(8), new Minute(0)),
                    new Time(new Hour(12), new Minute(59))
                ),
                new OpeningHour(
                    new Days(
                        Day::saturday()
                    ),
                    new Time(new Hour(10), new Minute(0)),
                    new Time(new Hour(14), new Minute(0))
                ),
            ]
        );

        $actual = Calendar::fromUdb3ModelCalendar($udb3ModelCalendar);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_determine_same_calendars(): void
    {
        $calendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2020-01-26T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-27T12:12:12+01:00')
        );

        $sameCalendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2020-01-26T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-27T12:12:12+01:00')
        );

        $otherCalendar = new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom('2020-01-27T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-01-28T12:12:12+01:00')
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
            CalendarType::multiple(),
            DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-04-30T12:12:12+01:00'),
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-05T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-10T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-07T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-09T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-15T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-25T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-20T12:12:12+01:00')
                ),
            ]
        );

        $expected = [
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-20T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-05T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-10T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-07T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-09T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-15T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-25T12:12:12+01:00')
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
                DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-20T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-05T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-10T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-07T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-09T12:12:12+01:00')
            ),
            new Timestamp(
                DateTimeFactory::fromAtom('2020-04-15T11:11:11+01:00'),
                DateTimeFactory::fromAtom('2020-04-25T12:12:12+01:00')
            ),
        ];

        $calendar = new Calendar(
            CalendarType::multiple(),
            DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-04-30T12:12:12+01:00'),
            $timestamps
        );

        $this->assertEquals(
            new Status(StatusType::Available(), null),
            $calendar->getStatus()
        );

        $newStatus = new Status(
            StatusType::Unavailable(),
            new TranslatedStatusReason(new Language('nl'), new StatusReason('Het mag niet van de afgevaardigde van de eerste minister'))
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
            CalendarType::multiple(),
            DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
            DateTimeFactory::fromAtom('2020-04-30T12:12:12+01:00'),
            [
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-05T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-10T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-07T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-09T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-15T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-25T12:12:12+01:00')
                ),
                new Timestamp(
                    DateTimeFactory::fromAtom('2020-04-01T11:11:11+01:00'),
                    DateTimeFactory::fromAtom('2020-04-20T12:12:12+01:00')
                ),
            ]
        );

        $this->assertEquals(
            new Status(StatusType::Available(), null),
            $calendar->getStatus()
        );

        $newStatus = new Status(
            StatusType::Unavailable(),
            new TranslatedStatusReason(new Language('nl'), new StatusReason('Het mag niet van de afgevaardigde van de eerste minister'))
        );

        $updatedCalendar = $calendar
            ->withStatus($newStatus)
            ->withStatusOnTimestamps($newStatus);

        $this->assertEquals($newStatus, $updatedCalendar->getStatus());

        foreach ($updatedCalendar->getTimestamps() as $timestamp) {
            $this->assertEquals($newStatus, $timestamp->getStatus());
        }
    }

    /**
     * @test
     */
    public function time_stamps_need_to_have_type_time_stamp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timestamps should have type TimeStamp.');

        new Calendar(
            CalendarType::single(),
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            ['wrong timestamp'] // @phpstan-ignore-line
        );
    }

    /**
     * @test
     */
    public function opening_hours_need_to_have_type_opening_hour(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OpeningHours should have type OpeningHour.');

        new Calendar(
            CalendarType::periodic(),
            DateTimeFactory::fromAtom(self::START_DATE),
            DateTimeFactory::fromAtom(self::END_DATE),
            [],
            ['wrong opening hours'] // @phpstan-ignore-line
        );
    }
}
