<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
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
                BookingAvailability::Available()
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
                        BookingAvailability::Available()
                    ),
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-04T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-05T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available()
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
                    BookingAvailability::Available()
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
                        BookingAvailability::Available()
                    ),
                    new SubEvent(
                        new DateRange(
                            new DateTimeImmutable('2021-01-04T00:00:00+01:00'),
                            new DateTimeImmutable('2021-01-05T00:00:00+01:00')
                        ),
                        new Status(StatusType::Available()),
                        BookingAvailability::Available()
                    )
                )
            ),
            CalendarSerializer::deserialize($data)
        );
    }
}
