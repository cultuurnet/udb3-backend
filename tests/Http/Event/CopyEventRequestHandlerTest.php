<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\FixedUuidFactory;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class CopyEventRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private CopyEventRequestHandler $copyEventRequestHandler;

    private const ORIGINAL_EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';
    private const NEW_EVENT_ID = '581ef86b-78bf-4083-ac88-a42d0f4dde87';

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $iriGenerator = new CallableIriGenerator(
            fn (string $id) => 'https://mock.io/events/' . $id
        );

        $this->copyEventRequestHandler = new CopyEventRequestHandler(
            $this->commandBus,
            new FixedUuidFactory(new Uuid(self::NEW_EVENT_ID)),
            $iriGenerator
        );
    }

    /**
     * @test
     * @dataProvider validEventDataProvider
     */
    public function it_does_not_throw_when_given_valid_event_data(object $data, CopyEvent $expectedCommand): void
    {
        $response = $this->copyEventRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withJsonBodyFromObject($data)
                ->withRouteParameter('eventId', self::ORIGINAL_EVENT_ID)
                ->build('POST')
        );

        $expectedJson = '{"eventId":"' . self::NEW_EVENT_ID . '","url":"https:\/\/mock.io\/events\/' . self::NEW_EVENT_ID . '"}';

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals($expectedJson, $response->getBody()->getContents());
    }

    public function validEventDataProvider(): array
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Available()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Available()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
                            )
                        )
                    )
                ),
            ],
            'periodic_deprecated_empty_timeSpans' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                    'timeSpans' => [],
                ],
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new PermanentCalendar(new OpeningHours())
                ),
            ],
            'single_startDate_and_endDate_instead_of_subEvent' => [
                'data' => (object) [
                    'calendarType' => 'single',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Available()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Unavailable()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Unavailable()
                            )
                        )
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Covid')
                                )
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    (new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Covid')
                                )
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Unavailable()
                            )
                        )
                    ))
                    ->withStatus(
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Covid')
                            )
                        )
                    )
                    ->withBookingAvailability(
                        new BookingAvailability(
                            BookingAvailabilityType::Unavailable()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Available()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new SingleSubEventCalendar(
                        new SubEvent(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            ),
                            new Status(
                                StatusType::Available()
                            ),
                            new BookingAvailability(
                                BookingAvailabilityType::Available()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            new SubEvent(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                                ),
                                new Status(
                                    StatusType::Available()
                                ),
                                new BookingAvailability(
                                    BookingAvailabilityType::Available()
                                )
                            ),
                            new SubEvent(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-03T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-03T17:00:30+01:00'),
                                ),
                                new Status(
                                    StatusType::Available()
                                ),
                                new BookingAvailability(
                                    BookingAvailabilityType::Available()
                                )
                            ),
                        )
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            new SubEvent(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
                                ),
                                new Status(
                                    StatusType::Available()
                                ),
                                new BookingAvailability(
                                    BookingAvailabilityType::Available()
                                )
                            ),
                            new SubEvent(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2021-01-03T14:00:30+01:00'),
                                    DateTimeFactory::fromAtom('2021-01-03T17:00:30+01:00'),
                                ),
                                new Status(
                                    StatusType::Available()
                                ),
                                new BookingAvailability(
                                    BookingAvailabilityType::Available()
                                )
                            ),
                        )
                    )
                ),
            ],
            'periodic' => [
                'data' => (object) [
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours()
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours()
                    ))
                    ->withStatus(
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::wednesday()),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(Day::tuesday(), Day::thursday()),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            )
                        )
                    )
                ),
            ],
            'permanent' => [
                'data' => (object) [
                    'calendarType' => 'permanent',
                ],
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new PermanentCalendar(new OpeningHours())
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    (new PermanentCalendar(new OpeningHours()))
                        ->withStatus(
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
                'expected_command' => new CopyEvent(
                    self::ORIGINAL_EVENT_ID,
                    self::NEW_EVENT_ID,
                    new PermanentCalendar(
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::wednesday()),
                                new Time(new Hour(10), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ),
                            new OpeningHour(
                                new Days(Day::tuesday(), Day::thursday()),
                                new Time(new Hour(8), new Minute(30)),
                                new Time(new Hour(9), new Minute(0))
                            )
                        )
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
            fn () => $this->copyEventRequestHandler->handle(
                $requestBuilder
                    ->withRouteParameter('eventId', self::ORIGINAL_EVENT_ID)
                    ->build('POST')
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
}
