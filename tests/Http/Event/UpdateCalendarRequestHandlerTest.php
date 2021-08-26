<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailabilityType;
use CultuurNet\UDB3\Timestamp;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UpdateCalendarRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateCalendarRequestHandler $updateCalendarRequestHandler;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->updateCalendarRequestHandler = new UpdateCalendarRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function it_does_not_throw_when_given_valid_data($data, UpdateCalendar $expectedCommand): void
    {
        $this->updateCalendarRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withBodyFromString(json_encode($data))
                ->withRouteParameter('eventId', self::EVENT_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validDataProvider(): array
    {
        return [
            'single' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new Calendar(
                        CalendarType::SINGLE(),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [
                            new Timestamp(
                                DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                                DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                            ),
                        ]
                    )
                ),
            ],
            'single_deprecated' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'timeSpans' => [
                        (object) [
                            'start' => '2021-01-01T14:00:30+01:00',
                            'end' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new Calendar(
                        CalendarType::SINGLE(),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [
                            new Timestamp(
                                DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                                DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                            ),
                        ]
                    )
                ),
            ],
            'single_with_custom_status_and_bookingAvailability' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object) ['type' => 'Unavailable'],
                            'bookingAvailability' => (object) ['type' => 'Unavailable'],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new Calendar(
                        CalendarType::SINGLE(),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [
                            (
                                new Timestamp(
                                    DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                                    DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                                )
                            )
                                ->withStatus(new Status(StatusType::unavailable(), []))
                                ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::unavailable())),
                        ]
                    )
                ),
            ],
            'single_with_custom_status_with_reason' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object) [
                                'type' => 'TemporarilyUnavailable',
                                'reason' => (object) ['nl' => 'Covid'],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new Calendar(
                        CalendarType::SINGLE(),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [
                            (
                                new Timestamp(
                                    DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                                    DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                                )
                            )
                                ->withStatus(
                                    new Status(
                                        StatusType::temporarilyUnavailable(),
                                        [new StatusReason(new Language('nl'), 'Covid')]
                                    )
                                )
                        ]
                    )
                ),
            ],
            'single_with_custom_status_with_reason_and_bookingAvailability_on_top_level_instead_of_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                    'status' => (object) [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object) ['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object) ['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (
                        new Calendar(
                            CalendarType::SINGLE(),
                            DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                            DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                            [
                                (
                                    new Timestamp(
                                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                                    )
                                )
                                    ->withStatus(
                                        new Status(
                                            StatusType::temporarilyUnavailable(),
                                            [new StatusReason(new Language('nl'), 'Covid')]
                                        )
                                    )
                                    ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::unavailable())),
                            ]
                        )
                    )
                        ->withStatus(
                            new Status(StatusType::temporarilyUnavailable(), [])
                        )
                        ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::unavailable()))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function it_throws_an_api_problem_when_given_invalid_data($data, array $expectedSchemaErrors): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->updateCalendarRequestHandler->handle(
                (new Psr7RequestBuilder())
                    ->withBodyFromString(json_encode($data))
                    ->withRouteParameter('eventId', self::EVENT_ID)
                    ->build('PUT')
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function invalidDataProvider(): array
    {
        return [
            'not_an_object' => [
                'data' => [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The data (array) must match the type: object'),
                ],
            ],
            'single_no_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'single',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (subEvent) are missing'),
                ],
            ],
            'subEvent_not_an_array' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => (object) [
                        'startDate' => '2021-01-01T17:00:30+01:00',
                        'endDate' => '2021-01-01T17:00:30+01:00',
                        'status' => (object) ['type' => 'Available'],
                        'bookingAvailability' => (object) ['type' => 'Available'],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent', 'The data (object) must match the type: array'),
                ],
            ],
            'subEvent_startDate_and_endDate_not_a_datetime' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => 'foo',
                            'endDate' => 'bar',
                            'status' => (object) ['type' => 'Available'],
                            'bookingAvailability' => (object) ['type' => 'Available'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/startDate', 'The data must match the \'date-time\' format'),
                    new SchemaError('/subEvent/0/endDate', 'The data must match the \'date-time\' format'),
                ],
            ],
            'subEvent_endDate_after_startDate' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T17:00:30+01:00',
                            'endDate' => '2021-01-01T14:00:30+01:00',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/endDate', 'endDate should not be before startDate'),
                ],
            ],
            'subEvent_status_and_bookingAvailability_incorrect_type' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => 'Should be object',
                            'bookingAvailability' => 'Should be object',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/status', 'The data (string) must match the type: object'),
                    new SchemaError('/subEvent/0/bookingAvailability', 'The data (string) must match the type: object'),
                ],
            ],
            'subEvent_status_and_bookingAvailability_types_incorrect_values' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object) ['type' => 'foo'],
                            'bookingAvailability' => (object) ['type' => 'foo'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/status/type', 'The data should match one item from enum'),
                    new SchemaError('/subEvent/0/bookingAvailability/type', 'The data should match one item from enum'),
                ],
            ],
        ];
    }
}
