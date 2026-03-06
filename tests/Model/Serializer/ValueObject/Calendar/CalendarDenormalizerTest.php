<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CalendarDenormalizerTest extends TestCase
{
    private CalendarDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new CalendarDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_calendar_with_booking_info_on_the_sub_event(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'user@example.com',
                    ],
                ],
            ],
        ];

        $expected = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                (new BookingInfo())
                    ->withTelephoneNumber(new TelephoneNumber('0123456789'))
                    ->withEmailAddress(new EmailAddress('user@example.com'))
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_top_level_booking_info_when_sub_event_has_none(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'bookingInfo' => [
                'phone' => '0123456789',
                'email' => 'user@example.com',
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
            ],
        ];

        $expected = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                (new BookingInfo())
                    ->withTelephoneNumber(new TelephoneNumber('0123456789'))
                    ->withEmailAddress(new EmailAddress('user@example.com'))
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_gives_sub_event_booking_info_priority_over_top_level_booking_info(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'bookingInfo' => [
                'phone' => '0000000000',
                'email' => 'toplevel@example.com',
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'subevent@example.com',
                    ],
                ],
            ],
        ];

        $expected = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                (new BookingInfo())
                    ->withTelephoneNumber(new TelephoneNumber('0123456789'))
                    ->withEmailAddress(new EmailAddress('subevent@example.com'))
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_calendar_without_booking_info_on_the_sub_event(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
            ],
        ];

        $expected = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                BookingAvailability::Available(),
                new BookingInfo()
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_top_level_booking_info_for_multiple_calendar_sub_events_without_booking_info(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'bookingInfo' => [
                'phone' => '0123456789',
                'email' => 'user@example.com',
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                ],
            ],
        ];

        $topLevelBookingInfo = (new BookingInfo())
            ->withTelephoneNumber(new TelephoneNumber('0123456789'))
            ->withEmailAddress(new EmailAddress('user@example.com'));

        $expected = new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    $topLevelBookingInfo
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    $topLevelBookingInfo
                )
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_multiple_calendar_with_booking_info_on_one_sub_event(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'user@example.com',
                    ],
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                ],
            ],
        ];

        $expected = new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    (new BookingInfo())
                        ->withTelephoneNumber(new TelephoneNumber('0123456789'))
                        ->withEmailAddress(new EmailAddress('user@example.com'))
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    new BookingInfo()
                )
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_multiple_calendar_with_booking_info_on_all_sub_events(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingInfo' => [
                        'phone' => '0123456789',
                        'email' => 'day1@example.com',
                    ],
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                    'bookingInfo' => [
                        'phone' => '0987654321',
                        'email' => 'day2@example.com',
                    ],
                ],
            ],
        ];

        $expected = new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    (new BookingInfo())
                        ->withTelephoneNumber(new TelephoneNumber('0123456789'))
                        ->withEmailAddress(new EmailAddress('day1@example.com'))
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available(),
                    (new BookingInfo())
                        ->withTelephoneNumber(new TelephoneNumber('0987654321'))
                        ->withEmailAddress(new EmailAddress('day2@example.com'))
                )
            )
        );

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_calendar_with_capacity_and_remaining_capacity(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'bookingAvailability' => [
                'capacity' => 300,
                'remainingCapacity' => 75,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
            ],
        ];

        $topLevelBookingAvailability = BookingAvailability::Available()
            ->withCapacity(300)
            ->withRemainingCapacity(75);

        $expected = (new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                $topLevelBookingAvailability,
                new BookingInfo()
            )
        ))->withBookingAvailability($topLevelBookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_falls_back_to_top_level_capacity_when_sub_event_has_none(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'bookingAvailability' => [
                'type' => 'Available',
                'capacity' => 200,
                'remainingCapacity' => 50,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
            ],
        ];

        $topLevelBookingAvailability = BookingAvailability::Available()
            ->withCapacity(200)
            ->withRemainingCapacity(50);

        $expected = (new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                $topLevelBookingAvailability,
                new BookingInfo()
            )
        ))->withBookingAvailability($topLevelBookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_gives_sub_event_capacity_priority_over_top_level_capacity(): void
    {
        $data = [
            'calendarType' => 'single',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'bookingAvailability' => [
                'type' => 'Available',
                'capacity' => 500,
                'remainingCapacity' => 250,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingAvailability' => [
                        'type' => 'Available',
                        'capacity' => 100,
                        'remainingCapacity' => 25,
                    ],
                ],
            ],
        ];

        $subEventBookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withRemainingCapacity(25);

        $topLevelBookingAvailability = BookingAvailability::Available()
            ->withCapacity(500)
            ->withRemainingCapacity(250);

        $expected = (new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                    new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                ),
                new Status(StatusType::Available()),
                $subEventBookingAvailability,
                new BookingInfo()
            )
        ))->withBookingAvailability($topLevelBookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_multiple_calendar_with_capacity_and_remaining_capacity_inherited_by_sub_events(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'bookingAvailability' => [
                'type' => 'Available',
                'capacity' => 500,
                'remainingCapacity' => 250,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                ],
            ],
        ];

        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(500)
            ->withRemainingCapacity(250);

        $expected = (new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    $bookingAvailability,
                    new BookingInfo()
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    $bookingAvailability,
                    new BookingInfo()
                )
            )
        ))->withBookingAvailability($bookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_denormalizes_a_multiple_calendar_with_different_capacity_on_each_sub_event(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'bookingAvailability' => [
                'type' => 'Available',
                'capacity' => 500,
                'remainingCapacity' => 250,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingAvailability' => [
                        'type' => 'Available',
                        'capacity' => 100,
                        'remainingCapacity' => 42,
                    ],
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                    'bookingAvailability' => [
                        'type' => 'Available',
                        'capacity' => 200,
                        'remainingCapacity' => 150,
                    ],
                ],
            ],
        ];

        $topLevelBookingAvailability = BookingAvailability::Available()
            ->withCapacity(500)
            ->withRemainingCapacity(250);

        $expected = (new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available()
                        ->withCapacity(100)
                        ->withRemainingCapacity(42),
                    new BookingInfo()
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available()
                        ->withCapacity(200)
                        ->withRemainingCapacity(150),
                    new BookingInfo()
                )
            )
        ))->withBookingAvailability($topLevelBookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }

    /**
     * @test
     */
    public function it_only_inherits_capacity_when_sub_event_has_no_explicit_capacity(): void
    {
        $data = [
            'calendarType' => 'multiple',
            'startDate' => '2021-05-17T08:00:00+00:00',
            'endDate' => '2021-05-18T22:00:00+00:00',
            'bookingAvailability' => [
                'type' => 'Available',
                'capacity' => 500,
                'remainingCapacity' => 250,
            ],
            'subEvent' => [
                [
                    'startDate' => '2021-05-17T08:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'bookingAvailability' => [
                        'type' => 'Available',
                        'remainingCapacity' => 42,
                    ],
                ],
                [
                    'startDate' => '2021-05-18T08:00:00+00:00',
                    'endDate' => '2021-05-18T22:00:00+00:00',
                ],
            ],
        ];

        $topLevelBookingAvailability = BookingAvailability::Available()
            ->withCapacity(500)
            ->withRemainingCapacity(250);

        $expected = (new MultipleSubEventsCalendar(
            new SubEvents(
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-17T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-17T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    BookingAvailability::Available()
                        ->withCapacity(500)
                        ->withRemainingCapacity(42),
                    new BookingInfo()
                ),
                new SubEvent(
                    new DateRange(
                        new DateTimeImmutable('2021-05-18T08:00:00+00:00'),
                        new DateTimeImmutable('2021-05-18T22:00:00+00:00')
                    ),
                    new Status(StatusType::Available()),
                    $topLevelBookingAvailability,
                    new BookingInfo()
                )
            )
        ))->withBookingAvailability($topLevelBookingAvailability);

        $this->assertEquals(
            $expected,
            $this->denormalizer->denormalize($data, Calendar::class)
        );
    }
}
