<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use PHPUnit\Framework\TestCase;

class UpdateCalendarRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateCalendarRequestHandler $updateCalendarRequestHandler;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';
    private const PLACE_ID = 'b30ec08f-d63d-4c89-ae09-f68b253cf97d';

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->updateCalendarRequestHandler = new UpdateCalendarRequestHandler($this->commandBus);
    }

    /**
     * @test
     * @dataProvider validEventDataProvider
     */
    public function it_does_not_throw_when_given_valid_event_data(object $data, UpdateCalendar $expectedCommand): void
    {
        $this->updateCalendarRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withJsonBodyFromObject($data)
                ->withRouteParameter('offerType', 'events')
                ->withRouteParameter('offerId', self::EVENT_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validEventDataProvider(): array
    {
        // WHEN UpdateCalendar GETS REFACTORED TO USE THE NEW CALENDAR VALUE-OBJECT LIKE CopyEvent, THIS TEST DATA CAN
        // EASILY BE REPLACED WITH THE TEST DATA FROM CopyEventRequestHandlerTest::validEventDataProvider() TO SAVE YOU
        // SOME TIME.
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
                    Calendar::single(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
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
                    Calendar::single(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
                    )
                ),
            ],
            'single_startDate_and_endDate_instead_of_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::single(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
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
                    Calendar::single(
                        (
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                                )
                            )
                        )
                            ->withStatus(new Status(StatusType::Unavailable(), null))
                            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()))
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
                    Calendar::single(
                        (
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                                )
                            )
                        )
                            ->withStatus(
                                new Status(
                                    StatusType::TemporarilyUnavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Covid')
                                    )
                                )
                            )
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
                        Calendar::single(
                            (
                                SubEvent::createAvailable(
                                    new DateRange(
                                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                                    )
                                )
                            )
                                ->withStatus(
                                    new Status(
                                        StatusType::TemporarilyUnavailable(),
                                        new TranslatedStatusReason(
                                            new Language('nl'),
                                            new StatusReason('Covid')
                                        )
                                    )
                                )
                                ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable())),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Covid')
                                )
                            ),
                            BookingAvailability::Unavailable()
                        )
                    )
                ),
            ],
            'multiple_with_one_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::single(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
                    )
                ),
            ],
            'multiple_with_startDate_and_endDate_instead_of_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'multiple',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::single(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
                    )
                ),
            ],
            'multiple' => [
                'data' => (object) [
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                        (object) [
                            'startDate' => '2021-01-03T14:00:30+01:00',
                            'endDate' => '2021-01-03T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::multiple(
                        [
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-03T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-03T17:00:30+01:00')
                                )
                            ),
                        ]
                    )
                ),
            ],
            'multiple_deprecated' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'timeSpans' => [
                        (object) [
                            'start' => '2021-01-01T14:00:30+01:00',
                            'end' => '2021-01-01T17:00:30+01:00',
                        ],
                        (object) [
                            'start' => '2021-01-03T14:00:30+01:00',
                            'end' => '2021-01-03T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::multiple(
                        [
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-03T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-03T17:00:30+01:00')
                                )
                            ),
                        ]
                    )
                ),
            ],
            'periodic' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                    )
                ),
            ],
            'periodic_with_status_and_bookingAvailability' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'status' => (object) [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object) ['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object) ['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                        [],
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Covid')
                            )
                        )
                    )
                ),
            ],
            'periodic_with_openingHours' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object) [
                            'opens' => '8:30',
                            'closes' => '9:00',
                            'dayOfWeek' => [
                                'tuesday',
                                'thursday',
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                        [
                            new OpeningHour(
                                new Days(
                                    Day::monday(),
                                    Day::wednesday()
                                ),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(
                                    Day::tuesday(),
                                    Day::thursday()
                                ),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            ),
                        ]
                    )
                ),
            ],
            'permanent' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::permanent()
                ),
            ],
            'permanent_with_status_and_bookingAvailability' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                    'status' => (object) [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object) ['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object) ['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::permanent(
                        [],
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Covid')
                            )
                        )
                    )
                ),
            ],
            'permanent_with_openingHours' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object) [
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object) [
                            'opens' => '8:30',
                            'closes' => '9:00',
                            'dayOfWeek' => [
                                'tuesday',
                                'thursday',
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    Calendar::permanent(
                        [
                            new OpeningHour(
                                new Days(
                                    Day::monday(),
                                    Day::wednesday()
                                ),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(
                                    Day::tuesday(),
                                    Day::thursday()
                                ),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            ),
                        ]
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidEventDataProvider
     * @param array|object $data
     */
    public function it_throws_an_api_problem_when_given_invalid_event_data($data, array $expectedSchemaErrors): void
    {
        $requestBuilder = new Psr7RequestBuilder();
        if (is_array($data)) {
            $requestBuilder = $requestBuilder->withJsonBodyFromArray($data);
        }
        if (is_object($data)) {
            $requestBuilder = $requestBuilder->withJsonBodyFromObject($data);
        }

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->updateCalendarRequestHandler->handle(
                $requestBuilder
                    ->withRouteParameter('offerType', 'events')
                    ->withRouteParameter('offerId', self::EVENT_ID)
                    ->build('PUT')
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function invalidEventDataProvider(): array
    {
        return [
            'not_an_object' => [
                'data' => [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The data (array) must match the type: object'),
                ],
            ],
            'calendar_type_missing' => [
                'data' => (object) [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (calendarType) are missing'),
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
            'single_empty_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'subEvent' => [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent', 'Array should have at least 1 items, 0 found'),
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
            'multiple_incorrect_subEvents' => [
                'data' => (object) [
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object) [
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object) ['type' => 'foo'],
                            'bookingAvailability' => (object) ['type' => 'foo'],
                        ],
                        (object) [
                            'startDate' => 'foo',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/status/type', 'The data should match one item from enum'),
                    new SchemaError('/subEvent/0/bookingAvailability/type', 'The data should match one item from enum'),
                    new SchemaError('/subEvent/1/startDate', 'The data must match the \'date-time\' format'),
                ],
            ],
            'periodic_no_startDate_and_endDate' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (startDate, endDate) are missing'),
                ],
            ],
            'periodic_invalid_startDate_and_endDate' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => 'foo',
                    'endDate' => false,
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/startDate', 'The data must match the \'date-time\' format'),
                    new SchemaError('/endDate', 'The data (boolean) must match the type: string'),
                ],
            ],
            'periodic_invalid_openingHours_type' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => 'foo',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_type' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => ['foo'],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The data (string) must match the type: object'),
                ],
            ],
            'periodic_invalid_openingHours_item_missing_required_fields' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The required properties (opens, closes, dayOfWeek) are missing'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_fields' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => 10,
                            'closes' => 'foo',
                            'dayOfWeek' => 'Monday',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/opens', 'The data (integer) must match the type: string'),
                    new SchemaError('/openingHours/0/closes', 'The string should match pattern: ^\d?\d:\d\d$'),
                    new SchemaError('/openingHours/0/dayOfWeek', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_dayOfWeek' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '8:00',
                            'closes' => '12:00',
                            'dayOfWeek' => [
                                'monday',
                                'foo',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/dayOfWeek/1', 'The data should match one item from enum'),
                ],
            ],
            'periodic_invalid_openingHours_item_close_before_open' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '12:00',
                            'closes' => '8:00',
                            'dayOfWeek' => [
                                'monday',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/closes', 'closes should not be before opens'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validPlaceDataProvider
     */
    public function it_does_not_throw_when_given_valid_place_data(object $data, UpdateCalendar $expectedCommand): void
    {
        $this->updateCalendarRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withJsonBodyFromObject($data)
                ->withRouteParameter('offerType', 'places')
                ->withRouteParameter('offerId', self::PLACE_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validPlaceDataProvider(): array
    {
        return [
            'periodic' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                    )
                ),
            ],
            'periodic_with_status_and_bookingAvailability' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'status' => (object) [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object) ['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object) ['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                        [],
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Covid')
                            )
                        )
                    )
                ),
            ],
            'periodic_with_openingHours' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object) [
                            'opens' => '8:30',
                            'closes' => '9:00',
                            'dayOfWeek' => [
                                'tuesday',
                                'thursday',
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::periodic(
                        DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                        DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                        [
                            new OpeningHour(
                                new Days(
                                    Day::monday(),
                                    Day::wednesday()
                                ),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(
                                    Day::tuesday(),
                                    Day::thursday()
                                ),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            ),
                        ]
                    )
                ),
            ],
            'permanent' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::permanent()
                ),
            ],
            'permanent_with_status_and_bookingAvailability' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                    'status' => (object) [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object) ['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object) ['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::permanent(
                        [],
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Covid')
                            )
                        )
                    )
                ),
            ],
            'permanent_with_openingHours' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object) [
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object) [
                            'opens' => '8:30',
                            'closes' => '9:00',
                            'dayOfWeek' => [
                                'tuesday',
                                'thursday',
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    Calendar::permanent(
                        [
                            new OpeningHour(
                                new Days(
                                    Day::monday(),
                                    Day::wednesday()
                                ),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(
                                    Day::tuesday(),
                                    Day::thursday()
                                ),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            ),
                        ]
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPlaceDataProvider
     * @param array|object $data
     */
    public function it_throws_an_api_problem_when_given_invalid_place_data($data, array $expectedSchemaErrors): void
    {
        $requestBuilder = new Psr7RequestBuilder();
        if (is_array($data)) {
            $requestBuilder = $requestBuilder->withJsonBodyFromArray($data);
        }
        if (is_object($data)) {
            $requestBuilder = $requestBuilder->withJsonBodyFromObject($data);
        }

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->updateCalendarRequestHandler->handle(
                $requestBuilder
                    ->withRouteParameter('offerType', 'places')
                    ->withRouteParameter('offerId', self::PLACE_ID)
                    ->build('PUT')
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function invalidPlaceDataProvider(): array
    {
        return [
            'not_an_object' => [
                'data' => [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The data (array) must match the type: object'),
                ],
            ],
            'calendar_type_missing' => [
                'data' => (object) [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (calendarType) are missing'),
                ],
            ],
            'calendar_type_single' => [
                'data' => (object) [
                    'calendarType' => 'single',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/calendarType', 'The data should match one item from enum'),
                ],
            ],
            'calendar_type_multiple' => [
                'data' => (object) [
                    'calendarType' => 'multiple',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/calendarType', 'The data should match one item from enum'),
                ],
            ],
            'periodic_no_startDate_and_endDate' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (startDate, endDate) are missing'),
                ],
            ],
            'periodic_invalid_startDate_and_endDate' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => 'foo',
                    'endDate' => false,
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/startDate', 'The data must match the \'date-time\' format'),
                    new SchemaError('/endDate', 'The data (boolean) must match the type: string'),
                ],
            ],
            'periodic_invalid_endDate' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T10:00:30+01:00',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/endDate', 'endDate should not be before startDate'),
                ],
            ],
            'periodic_invalid_openingHours_type' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => 'foo',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_type' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => ['foo'],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The data (string) must match the type: object'),
                ],
            ],
            'periodic_invalid_openingHours_item_missing_required_fields' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The required properties (opens, closes, dayOfWeek) are missing'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_fields' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => 10,
                            'closes' => 'foo',
                            'dayOfWeek' => 'Monday',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/opens', 'The data (integer) must match the type: string'),
                    new SchemaError('/openingHours/0/closes', 'The string should match pattern: ^\d?\d:\d\d$'),
                    new SchemaError('/openingHours/0/dayOfWeek', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_dayOfWeek' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '8:00',
                            'closes' => '12:00',
                            'dayOfWeek' => [
                                'monday',
                                'foo',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/dayOfWeek/1', 'The data should match one item from enum'),
                ],
            ],
            'periodic_invalid_openingHours_item_closing_time' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object) [
                            'opens' => '12:00',
                            'closes' => '08:00',
                            'dayOfWeek' => [
                                'monday',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/closes', 'closes should not be before opens'),
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_throw_if_body_is_missing(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::EVENT_ID)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateCalendarRequestHandler->handle($request)
        );
    }
}
