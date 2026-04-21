<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithAdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CalendarSerializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_a_calendar(): void
    {
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                    new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                new BookingInfo(),
            )
        );

        $serializer = new CalendarSerializer($calendar);

        $this->assertEquals(
            [
                'type' => 'single',
                'status' => [
                    'type' => 'Available',
                ],
                'bookingAvailability' => [
                    'type' => 'Available',
                ],
                'timestamps' => [
                    0 => [
                        'startDate' => '2021-01-01T00:00:00+01:00',
                        'endDate' => '2021-01-02T00:00:00+01:00',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                    ],
                ],
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-02T00:00:00+01:00',
            ],
            $serializer->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_a_calendar_with_booking_info_on_sub_event(): void
    {
        $bookingInfo = (new BookingInfo())
            ->withTelephoneNumber(new TelephoneNumber('0123456789'))
            ->withEmailAddress(new EmailAddress('user@example.com'));

        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                    new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                $bookingInfo,
            )
        );

        $serializer = new CalendarSerializer($calendar);

        $this->assertEquals(
            [
                'type' => 'single',
                'status' => [
                    'type' => 'Available',
                ],
                'bookingAvailability' => [
                    'type' => 'Available',
                ],
                'timestamps' => [
                    0 => [
                        'startDate' => '2021-01-01T00:00:00+01:00',
                        'endDate' => '2021-01-02T00:00:00+01:00',
                        'status' => [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => [
                            'type' => 'Available',
                        ],
                        'bookingInfo' => [
                            'phone' => '0123456789',
                            'email' => 'user@example.com',
                        ],
                    ],
                ],
                'startDate' => '2021-01-01T00:00:00+01:00',
                'endDate' => '2021-01-02T00:00:00+01:00',
            ],
            $serializer->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_calendar_with_booking_info_on_sub_event(): void
    {
        $data = [
            'type' => 'single',
            'status' => [
                'type' => 'Available',
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'timestamps' => [
                0 => [
                    'startDate' => '2021-01-01T00:00:00+01:00',
                    'endDate' => '2021-01-02T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'user@example.com',
                    ],
                ],
            ],
            'startDate' => '2021-01-01T00:00:00+01:00',
            'endDate' => '2021-01-02T00:00:00+01:00',
        ];

        $bookingInfo = (new BookingInfo())
            ->withTelephoneNumber(new TelephoneNumber('0123456789'))
            ->withEmailAddress(new EmailAddress('user@example.com'));

        $this->assertEquals(
            new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                        new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    $bookingInfo,
                )
            ),
            CalendarSerializer::deserialize($data)
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_calendar(): void
    {
        $data = [
            'type' => 'multiple',
            'status' => [
                'type' => 'Available',
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'timestamps' => [
                0 => [
                    'startDate' => '2021-01-01T00:00:00+01:00',
                    'endDate' => '2021-01-02T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
                1 => [
                    'startDate' => '2021-01-04T00:00:00+01:00',
                    'endDate' => '2021-01-05T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
            ],
            'startDate' => '2021-01-01T00:00:00+01:00',
            'endDate' => '2021-01-05T00:00:00+01:00',
        ];

        $this->assertEquals(
            new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available(),
                        new BookingInfo(),
                    ),
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-04T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-05T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available(),
                        new BookingInfo(),
                    )
                )
            ),
            CalendarSerializer::deserialize($data)
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_single_calendar_on_single_timestamp(): void
    {
        $data = [
            'type' => 'multiple',
            'status' => [
                'type' => 'Available',
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'timestamps' => [
                0 => [
                    'startDate' => '2021-01-01T00:00:00+01:00',
                    'endDate' => '2021-01-02T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
            ],
            'startDate' => '2021-01-01T00:00:00+01:00',
            'endDate' => '2021-01-02T00:00:00+01:00',
        ];

        $this->assertEquals(
            new SingleSubEventCalendar(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                        new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    new BookingInfo(),
                )
            ),
            CalendarSerializer::deserialize($data)
        );
    }

    /**
     * @test
     */
    public function it_upgrades_to_multiple_calendar_on_multiple_timestamp(): void
    {
        $data = [
            'type' => 'single',
            'status' => [
                'type' => 'Available',
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'timestamps' => [
                0 => [
                    'startDate' => '2021-01-01T00:00:00+01:00',
                    'endDate' => '2021-01-02T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
                1 => [
                    'startDate' => '2021-01-04T00:00:00+01:00',
                    'endDate' => '2021-01-05T00:00:00+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
            ],
            'startDate' => '2021-01-01T00:00:00+01:00',
            'endDate' => '2021-01-05T00:00:00+01:00',
        ];

        $this->assertEquals(
            new MultipleSubEventsCalendar(
                new SubEvents(
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-01T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-02T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available(),
                        new BookingInfo(),
                    ),
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-04T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-05T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available(),
                        new BookingInfo(),
                    )
                )
            ),
            CalendarSerializer::deserialize($data)
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_a_periodic_calendar_with_closed_days(): void
    {
        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );
        $calendar = new PeriodicCalendar(
            new DateRange(
                new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
                new DateTimeImmutable('2024-12-31T23:59:59+00:00')
            ),
            new OpeningHours()
        );
        $calendar = $calendar->withClosedDays(new ClosedDays($closedDay));

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayHasKey('openingHoursClosedDays', $data);
        $this->assertCount(1, $data['openingHoursClosedDays']);
        $this->assertEquals('2024-12-25', $data['openingHoursClosedDays'][0]['startDate']);
        $this->assertEquals('2024-12-25', $data['openingHoursClosedDays'][0]['endDate']);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_periodic_calendar_with_closed_days(): void
    {
        $data = [
            'type' => 'periodic',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHours' => [],
            'openingHoursClosedDays' => [
                [
                    'startDate' => '2024-12-25',
                    'endDate' => '2024-12-25',
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        $this->assertInstanceOf(PeriodicCalendar::class, $calendar);
        $this->assertInstanceOf(CalendarWithClosedDays::class, $calendar);
        /** @var CalendarWithClosedDays $calendar */
        $this->assertFalse($calendar->getClosedDays()->isEmpty());
        $this->assertEquals(1, $calendar->getClosedDays()->count());

        $closedDays = $calendar->getClosedDays()->toArray();
        // Date-only format creates DateTimeImmutable, check date part only
        $this->assertEquals('2024-12-25', $closedDays[0]->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2024-12-25', $closedDays[0]->getEndDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_can_serialize_a_permanent_calendar_with_closed_days(): void
    {
        $closedDay1 = new ClosedDay(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01')
        );
        $closedDay2 = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withClosedDays(new ClosedDays($closedDay1, $closedDay2));

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayHasKey('openingHoursClosedDays', $data);
        $this->assertCount(2, $data['openingHoursClosedDays']);
        // Should be sorted by startDate
        $this->assertEquals('2024-01-01', $data['openingHoursClosedDays'][0]['startDate']);
        $this->assertEquals('2024-12-25', $data['openingHoursClosedDays'][1]['startDate']);
    }

    /**
     * @test
     */
    public function it_can_serialize_a_periodic_calendar_with_adjusted_opening_hours(): void
    {
        $adjustedDays = new AdjustedDay(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(
                    new Days(Day::friday()),
                    Time::fromString('13:00'),
                    Time::fromString('15:00')
                )
            )
        );
        $calendar = new PeriodicCalendar(
            new DateRange(
                new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                new DateTimeImmutable('2026-12-31T23:59:59+00:00')
            ),
            new OpeningHours()
        );
        $calendar = $calendar->withAdjustedDays(new AdjustedDays($adjustedDays));

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayHasKey('openingHoursAdjustedDays', $data);
        $this->assertCount(1, $data['openingHoursAdjustedDays']);
        $this->assertEquals('2026-12-21', $data['openingHoursAdjustedDays'][0]['startDate']);
        $this->assertEquals('2026-12-26', $data['openingHoursAdjustedDays'][0]['endDate']);
        $this->assertArrayNotHasKey('description', $data['openingHoursAdjustedDays'][0]);
    }

    /**
     * @test
     */
    public function it_can_serialize_a_periodic_calendar_with_adjusted_opening_hours_and_description(): void
    {
        $description = new TranslatedAdjustedDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );
        $adjustedDays = new AdjustedDay(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            ),
            $description
        );
        $calendar = new PeriodicCalendar(
            new DateRange(
                new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                new DateTimeImmutable('2026-12-31T23:59:59+00:00')
            ),
            new OpeningHours()
        );
        $calendar = $calendar->withAdjustedDays(new AdjustedDays($adjustedDays));

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayHasKey('openingHoursAdjustedDays', $data);
        $this->assertEquals('Kerstvakantie', $data['openingHoursAdjustedDays'][0]['description']['nl']);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_periodic_calendar_with_adjusted_opening_hours(): void
    {
        $data = [
            'type' => 'periodic',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHours' => [],
            'openingHoursAdjustedDays' => [
                [
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        [
                            'opens' => '13:00',
                            'closes' => '15:00',
                            'dayOfWeek' => ['friday'],
                        ],
                    ],
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        $this->assertInstanceOf(PeriodicCalendar::class, $calendar);
        $this->assertInstanceOf(CalendarWithAdjustedDays::class, $calendar);
        /** @var CalendarWithAdjustedDays $calendar */
        $this->assertFalse($calendar->getAdjustedDays()->isEmpty());
        $this->assertEquals(1, $calendar->getAdjustedDays()->count());

        $entries = $calendar->getAdjustedDays()->toArray();
        $this->assertEquals('2026-12-21', $entries[0]->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2026-12-26', $entries[0]->getEndDate()->format('Y-m-d'));
        $this->assertNull($entries[0]->getDescription());
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_periodic_calendar_with_adjusted_opening_hours_and_description(): void
    {
        $data = [
            'type' => 'periodic',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHours' => [],
            'openingHoursAdjustedDays' => [
                [
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        ['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                    'description' => ['nl' => 'Kerstvakantie', 'fr' => 'Vacances de Noël'],
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        /** @var CalendarWithAdjustedDays $calendar */
        $entries = $calendar->getAdjustedDays()->toArray();
        $this->assertNotNull($entries[0]->getDescription());
        $this->assertEquals('Kerstvakantie', $entries[0]->getDescription()->getTranslation(new Language('nl'))->toString());
        $this->assertEquals('Vacances de Noël', $entries[0]->getDescription()->getTranslation(new Language('fr'))->toString());
    }

    /**
     * @test
     */
    public function it_can_serialize_a_permanent_calendar_with_adjusted_opening_hours(): void
    {
        $adjustedDays = new AdjustedDay(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            )
        );
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withAdjustedDays(new AdjustedDays($adjustedDays));

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayHasKey('openingHoursAdjustedDays', $data);
        $this->assertCount(1, $data['openingHoursAdjustedDays']);
        $this->assertEquals('2026-12-21', $data['openingHoursAdjustedDays'][0]['startDate']);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_permanent_calendar_with_adjusted_opening_hours(): void
    {
        $data = [
            'type' => 'permanent',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'openingHours' => [],
            'openingHoursAdjustedDays' => [
                [
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        ['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        $this->assertInstanceOf(PermanentCalendar::class, $calendar);
        $this->assertInstanceOf(CalendarWithAdjustedDays::class, $calendar);
        /** @var CalendarWithAdjustedDays $calendar */
        $this->assertFalse($calendar->getAdjustedDays()->isEmpty());
        $this->assertEquals(1, $calendar->getAdjustedDays()->count());

        $entries = $calendar->getAdjustedDays()->toArray();
        $this->assertEquals('2026-12-21', $entries[0]->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2026-12-26', $entries[0]->getEndDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function it_skips_adjusted_opening_hours_entries_with_empty_opening_hours_on_deserialize(): void
    {
        // Old event-store data may contain entries with empty openingHours (written before the invariant was enforced).
        // These should be silently skipped rather than causing a fatal error.
        $data = [
            'type' => 'permanent',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'openingHours' => [],
            'openingHoursAdjustedDays' => [
                [
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [],
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        $this->assertInstanceOf(CalendarWithAdjustedDays::class, $calendar);
        /** @var CalendarWithAdjustedDays $calendar */
        $this->assertTrue($calendar->getAdjustedDays()->isEmpty());
    }

    /**
     * @test
     */
    public function it_does_not_serialize_adjusted_opening_hours_when_empty(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $serializer = new CalendarSerializer($calendar);
        $data = $serializer->serialize();

        $this->assertArrayNotHasKey('openingHoursAdjustedDays', $data);
    }

    /**
     * @test
     */
    public function it_can_remove_adjusted_opening_hours_by_passing_an_empty_collection(): void
    {
        $adjustedDays = new AdjustedDay(
            new DateTimeImmutable('2026-12-21'),
            new DateTimeImmutable('2026-12-26'),
            new OpeningHours(
                new OpeningHour(new Days(Day::friday()), Time::fromString('13:00'), Time::fromString('15:00'))
            )
        );
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withAdjustedDays(new AdjustedDays($adjustedDays));

        // Verify it was set
        $this->assertEquals(1, $calendar->getAdjustedDays()->count());

        // Remove by passing an empty collection
        $calendar = $calendar->withAdjustedDays(new AdjustedDays());

        $this->assertTrue($calendar->getAdjustedDays()->isEmpty());

        // Confirm it is also absent from the serialized output
        $data = (new CalendarSerializer($calendar))->serialize();
        $this->assertArrayNotHasKey('openingHoursAdjustedDays', $data);
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_permanent_calendar_with_closed_days(): void
    {
        $data = [
            'type' => 'permanent',
            'status' => ['type' => 'Available'],
            'bookingAvailability' => ['type' => 'Available'],
            'openingHours' => [],
            'openingHoursClosedDays' => [
                [
                    'startDate' => '2024-12-25',
                    'endDate' => '2024-12-25',
                ],
                [
                    'startDate' => '2024-01-01',
                    'endDate' => '2024-01-01',
                ],
            ],
        ];

        $calendar = CalendarSerializer::deserialize($data);

        $this->assertInstanceOf(PermanentCalendar::class, $calendar);
        $this->assertInstanceOf(CalendarWithClosedDays::class, $calendar);
        /** @var CalendarWithClosedDays $calendar */
        $this->assertFalse($calendar->getClosedDays()->isEmpty());
        $this->assertEquals(2, $calendar->getClosedDays()->count());

        // Should be sorted by startDate
        $closedDays = $calendar->getClosedDays()->toArray();
        // Date-only format creates DateTimeImmutable, check date part only
        $this->assertEquals('2024-01-01', $closedDays[0]->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2024-12-25', $closedDays[1]->getStartDate()->format('Y-m-d'));
    }
}
