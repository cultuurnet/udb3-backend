<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class UpdateCalendarRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateCalendarRequestHandler $updateCalendarRequestHandler;

    private const PLACE_ID = 'b30ec08f-d63d-4c89-ae09-f68b253cf97d';

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
                ->withRouteParameter('placeId', self::PLACE_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validDataProvider(): array
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
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00')
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
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [],
                        new Status(
                            StatusType::temporarilyUnavailable(),
                            [new StatusReason(new Language('nl'), 'Covid')]
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
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T14:00:30+01:00'),
                        DateTimeImmutable::createFromFormat(DATE_ATOM, '2021-01-01T17:00:30+01:00'),
                        [
                            new OpeningHour(
                                new OpeningTime(new Hour(10), new Minute(0)),
                                new OpeningTime(new Hour(17), new Minute(0)),
                                new DayOfWeekCollection(
                                    DayOfWeek::MONDAY(),
                                    DayOfWeek::WEDNESDAY()
                                )
                            ),
                            new OpeningHour(
                                new OpeningTime(new Hour(8), new Minute(30)),
                                new OpeningTime(new Hour(9), new Minute(0)),
                                new DayOfWeekCollection(
                                    DayOfWeek::TUESDAY(),
                                    DayOfWeek::THURSDAY()
                                )
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
                            StatusType::temporarilyUnavailable(),
                            [new StatusReason(new Language('nl'), 'Covid')]
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
                                new OpeningTime(new Hour(10), new Minute(0)),
                                new OpeningTime(new Hour(17), new Minute(0)),
                                new DayOfWeekCollection(
                                    DayOfWeek::MONDAY(),
                                    DayOfWeek::WEDNESDAY()
                                )
                            ),
                            new OpeningHour(
                                new OpeningTime(new Hour(8), new Minute(30)),
                                new OpeningTime(new Hour(9), new Minute(0)),
                                new DayOfWeekCollection(
                                    DayOfWeek::TUESDAY(),
                                    DayOfWeek::THURSDAY()
                                )
                            ),
                        ]
                    )
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
                    ->withRouteParameter('placeId', self::PLACE_ID)
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
}
