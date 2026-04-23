<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
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
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UpdateCalendarRequestHandlerTest extends TestCase
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'timeSpans' => [
                        (object)[
                            'start' => '2021-01-01T14:00:30+01:00',
                            'end' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object)['type' => 'Unavailable'],
                            'bookingAvailability' => (object)['type' => 'Unavailable'],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                            )
                        )
                            ->withStatus(new Status(StatusType::Unavailable(), null))
                            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()))
                    )
                ),
            ],
            'single_with_custom_status_with_reason' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object)[
                                'type' => 'TemporarilyUnavailable',
                                'reason' => (object)['nl' => 'Covid'],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                    'status' => (object)[
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object)['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object)['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new SingleSubEventCalendar(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                                DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00'),
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
                            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()))
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
                        ->withBookingAvailability(BookingAvailability::Unavailable())
                ),
            ],
            'multiple_with_one_subEvent' => [
                'data' => (object)[
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
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
                'data' => (object)[
                    'calendarType' => 'multiple',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
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
                'data' => (object)[
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                        ],
                        (object)[
                            'startDate' => '2021-01-03T14:00:30+01:00',
                            'endDate' => '2021-01-03T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new MultipleSubEventsCalendar(
                        new SubEvents(
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
                            )
                        )
                    )
                ),
            ],
            'multiple_deprecated' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'timeSpans' => [
                        (object)[
                            'start' => '2021-01-01T14:00:30+01:00',
                            'end' => '2021-01-01T17:00:30+01:00',
                        ],
                        (object)[
                            'start' => '2021-01-03T14:00:30+01:00',
                            'end' => '2021-01-03T17:00:30+01:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new MultipleSubEventsCalendar(
                        new SubEvents(
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
                            )
                        )
                    )
                ),
            ],
            'periodic' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'status' => (object)[
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object)['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object)['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours(),
                    ))->withStatus(
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object)[
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
                    new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours(
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
                            )
                        )
                    )
                ),
            ],
            'permanent' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new PermanentCalendar(new OpeningHours())
                ),
            ],
            'permanent_with_status_and_bookingAvailability' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'status' => (object)[
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object)['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object)['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
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
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object)[
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
                    new PermanentCalendar(
                        new OpeningHours(
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
                            )
                        )
                    )
                ),
            ],
            'periodic_with_childcare_on_opening_hours' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => '18:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours(
                            (new OpeningHour(
                                new Days(Day::monday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ))->withChildcareTimeRange(new TimeImmutableRange(
                                Time::fromString('08:00'),
                                Time::fromString('18:00')
                            ))
                        )
                    )
                ),
            ],
            'permanent_with_childcare_on_opening_hours' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => '18:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new PermanentCalendar(
                        new OpeningHours(
                            (new OpeningHour(
                                new Days(Day::monday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            ))->withChildcareTimeRange(new TimeImmutableRange(
                                Time::fromString('08:00'),
                                Time::fromString('18:00')
                            ))
                        )
                    )
                ),
            ],
            'periodic_with_single_closed_day' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-25',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromISO8601('2024-01-01T00:00:00+00:00'),
                            DateTimeFactory::fromISO8601('2024-12-31T23:59:59+00:00')
                        ),
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::tuesday(), Day::wednesday(), Day::thursday(), Day::friday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            )
                        )
                    ))->withClosedDays(
                        new ClosedDays(
                            new ClosedDay(
                                DateTimeFactory::fromDateOrISO8601('2024-12-25'),
                                DateTimeFactory::fromDateOrISO8601('2024-12-25')
                            )
                        )
                    )
                ),
            ],
            'periodic_with_multiple_closed_days' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-06-15',
                            'endDate' => '2024-06-15',
                        ],
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-26',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromISO8601('2024-01-01T00:00:00+00:00'),
                            DateTimeFactory::fromISO8601('2024-12-31T23:59:59+00:00')
                        ),
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            )
                        )
                    ))->withClosedDays(
                        new ClosedDays(
                            new ClosedDay(
                                DateTimeFactory::fromDateOrISO8601('2024-06-15'),
                                DateTimeFactory::fromDateOrISO8601('2024-06-15')
                            ),
                            new ClosedDay(
                                DateTimeFactory::fromDateOrISO8601('2024-12-25'),
                                DateTimeFactory::fromDateOrISO8601('2024-12-26')
                            )
                        )
                    )
                ),
            ],
            'periodic_with_adjusted_opening_hours' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday', 'saturday', 'sunday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2026-01-01T00:00:00+00:00'),
                            DateTimeFactory::fromAtom('2026-12-31T23:59:59+00:00')
                        ),
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::tuesday(), Day::wednesday(), Day::thursday(), Day::friday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            )
                        )
                    ))->withAdjustedDays(
                        new AdjustedDays(
                            new AdjustedDay(
                                DateTimeFactory::fromDateOrISO8601('2026-12-21'),
                                DateTimeFactory::fromDateOrISO8601('2026-12-26'),
                                new OpeningHours(
                                    new OpeningHour(
                                        new Days(Day::friday(), Day::saturday(), Day::sunday()),
                                        new Time(new Hour(13), new Minute(0)),
                                        new Time(new Hour(15), new Minute(0))
                                    )
                                )
                            )
                        )
                    )
                ),
            ],
            'periodic_with_adjusted_opening_hours_and_description' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'description' => (object)['nl' => 'Kerstvakantie', 'fr' => 'Vacances de Noël'],
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday', 'saturday', 'sunday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2026-01-01T00:00:00+00:00'),
                            DateTimeFactory::fromAtom('2026-12-31T23:59:59+00:00')
                        ),
                        new OpeningHours()
                    ))->withAdjustedDays(
                        new AdjustedDays(
                            new AdjustedDay(
                                DateTimeFactory::fromDateOrISO8601('2026-12-21'),
                                DateTimeFactory::fromDateOrISO8601('2026-12-26'),
                                new OpeningHours(
                                    new OpeningHour(
                                        new Days(Day::friday(), Day::saturday(), Day::sunday()),
                                        new Time(new Hour(13), new Minute(0)),
                                        new Time(new Hour(15), new Minute(0))
                                    )
                                ),
                                (new TranslatedAdjustedDescription(
                                    new Language('nl'),
                                    new AdjustedDescription('Kerstvakantie')
                                ))->withTranslation(
                                    new Language('fr'),
                                    new AdjustedDescription('Vacances de Noël')
                                )
                            )
                        )
                    )
                ),
            ],
            'periodic_with_multiple_adjusted_opening_hours_sorted_by_start_date' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-27',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)[
                                    'opens' => '14:00',
                                    'closes' => '16:00',
                                    'dayOfWeek' => ['saturday', 'sunday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2026-01-01T00:00:00+00:00'),
                            DateTimeFactory::fromAtom('2026-12-31T23:59:59+00:00')
                        ),
                        new OpeningHours()
                    ))->withAdjustedDays(
                        new AdjustedDays(
                            new AdjustedDay(
                                DateTimeFactory::fromDateOrISO8601('2026-12-21'),
                                DateTimeFactory::fromDateOrISO8601('2026-12-26'),
                                new OpeningHours(
                                    new OpeningHour(
                                        new Days(Day::friday()),
                                        new Time(new Hour(13), new Minute(0)),
                                        new Time(new Hour(15), new Minute(0))
                                    )
                                )
                            ),
                            new AdjustedDay(
                                DateTimeFactory::fromDateOrISO8601('2026-12-27'),
                                DateTimeFactory::fromDateOrISO8601('2026-12-31'),
                                new OpeningHours(
                                    new OpeningHour(
                                        new Days(Day::saturday(), Day::sunday()),
                                        new Time(new Hour(14), new Minute(0)),
                                        new Time(new Hour(16), new Minute(0))
                                    )
                                )
                            )
                        )
                    )
                ),
            ],
            'permanent_with_adjusted_opening_hours' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-24',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                    'dayOfWeek' => ['wednesday', 'thursday', 'friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PermanentCalendar(
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::tuesday(), Day::wednesday(), Day::thursday(), Day::friday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            )
                        )
                    ))->withAdjustedDays(
                        new AdjustedDays(
                            new AdjustedDay(
                                DateTimeFactory::fromDateOrISO8601('2026-12-24'),
                                DateTimeFactory::fromDateOrISO8601('2026-12-26'),
                                new OpeningHours(
                                    new OpeningHour(
                                        new Days(Day::wednesday(), Day::thursday(), Day::friday()),
                                        new Time(new Hour(10), new Minute(0)),
                                        new Time(new Hour(14), new Minute(0))
                                    )
                                )
                            )
                        )
                    )
                ),
            ],
            'permanent_with_closed_days' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-25',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    (new PermanentCalendar(
                        new OpeningHours(
                            new OpeningHour(
                                new Days(Day::monday(), Day::tuesday(), Day::wednesday(), Day::thursday(), Day::friday(), Day::saturday(), Day::sunday()),
                                new Time(new Hour(9), new Minute(0)),
                                new Time(new Hour(17), new Minute(0))
                            )
                        )
                    ))->withClosedDays(
                        new ClosedDays(
                            new ClosedDay(
                                DateTimeFactory::fromDateOrISO8601('2024-12-25'),
                                DateTimeFactory::fromDateOrISO8601('2024-12-25')
                            )
                        )
                    )
                ),
            ],
            'single_with_overnight_false' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-07-01T09:00:00+02:00',
                            'endDate' => '2026-07-05T17:00:00+02:00',
                            'overnight' => false,
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2026-07-01T09:00:00+02:00'),
                                DateTimeFactory::fromAtom('2026-07-05T17:00:00+02:00')
                            )
                        )
                    )
                ),
            ],
            'single_with_overnight_true' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-07-01T09:00:00+02:00',
                            'endDate' => '2026-07-05T17:00:00+02:00',
                            'overnight' => true,
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new SingleSubEventCalendar(
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeFactory::fromAtom('2026-07-01T09:00:00+02:00'),
                                DateTimeFactory::fromAtom('2026-07-05T17:00:00+02:00')
                            )
                        )->withOvernight(true)
                    )
                ),
            ],
            'multiple_with_overnight_on_first_sub_event' => [
                'data' => (object)[
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-07-01T09:00:00+02:00',
                            'endDate' => '2026-07-05T17:00:00+02:00',
                            'overnight' => true,
                        ],
                        (object)[
                            'startDate' => '2026-07-10T09:00:00+02:00',
                            'endDate' => '2026-07-14T17:00:00+02:00',
                        ],
                    ],
                ],
                'expected_command' => new UpdateCalendar(
                    self::EVENT_ID,
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2026-07-01T09:00:00+02:00'),
                                    DateTimeFactory::fromAtom('2026-07-05T17:00:00+02:00')
                                )
                            )->withOvernight(true),
                            SubEvent::createAvailable(
                                new DateRange(
                                    DateTimeFactory::fromAtom('2026-07-10T09:00:00+02:00'),
                                    DateTimeFactory::fromAtom('2026-07-14T17:00:00+02:00')
                                )
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
                'data' => (object)[],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (calendarType) are missing'),
                ],
            ],
            'single_no_subEvent' => [
                'data' => (object)[
                    'calendarType' => 'single',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (subEvent) are missing'),
                ],
            ],
            'single_empty_subEvent' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent', 'Array should have at least 1 items, 0 found'),
                ],
            ],
            'subEvent_not_an_array' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => (object)[
                        'startDate' => '2021-01-01T17:00:30+01:00',
                        'endDate' => '2021-01-01T17:00:30+01:00',
                        'status' => (object)['type' => 'Available'],
                        'bookingAvailability' => (object)['type' => 'Available'],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent', 'The data (object) must match the type: array'),
                ],
            ],
            'subEvent_startDate_and_endDate_not_a_datetime' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => 'foo',
                            'endDate' => 'bar',
                            'status' => (object)['type' => 'Available'],
                            'bookingAvailability' => (object)['type' => 'Available'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/startDate', 'The data must match the \'date-time\' format'),
                    new SchemaError('/subEvent/0/endDate', 'The data must match the \'date-time\' format'),
                ],
            ],
            'subEvent_endDate_after_startDate' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
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
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object)['type' => 'foo'],
                            'bookingAvailability' => (object)['type' => 'foo'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/status/type', 'The data should match one item from enum'),
                    new SchemaError('/subEvent/0/bookingAvailability/type', 'The data should match one item from enum'),
                ],
            ],
            'multiple_incorrect_subEvents' => [
                'data' => (object)[
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T14:00:30+01:00',
                            'endDate' => '2021-01-01T17:00:30+01:00',
                            'status' => (object)['type' => 'foo'],
                            'bookingAvailability' => (object)['type' => 'foo'],
                        ],
                        (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (startDate, endDate) are missing'),
                ],
            ],
            'periodic_invalid_startDate_and_endDate' => [
                'data' => (object)[
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
                'data' => (object)[
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
                'data' => (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The required properties (opens, closes, dayOfWeek) are missing'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_fields' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => 10,
                            'closes' => 'foo',
                            'dayOfWeek' => 'Monday',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/opens', 'The data (integer) must match the type: string'),
                    new SchemaError('/openingHours/0/closes', 'The string should match pattern: ^([01]?\d|2[0-3]):[0-5]\d$'),
                    new SchemaError('/openingHours/0/dayOfWeek', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_dayOfWeek' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
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
            'periodic_childcare_start_invalid_format' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '8:0'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/start', 'The string should match pattern: ^\d?\d:\d\d$'),
                ],
            ],
            'periodic_childcare_end_wrong_type' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => 1800],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/end', 'The data (integer) must match the type: string'),
                ],
            ],
            'periodic_childcare_start_equals_opens' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '09:00', 'end' => '18:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/start', 'childcare.start must be before opens'),
                ],
            ],
            'periodic_childcare_start_after_opens' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '10:00', 'end' => '18:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/start', 'childcare.start must be before opens'),
                ],
            ],
            'periodic_childcare_end_equals_closes' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => '17:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/end', 'childcare.end must be after closes'),
                ],
            ],
            'periodic_childcare_end_before_closes' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => '16:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/end', 'childcare.end must be after closes'),
                ],
            ],
            'permanent_childcare_start_equals_opens' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '09:00', 'end' => '18:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/start', 'childcare.start must be before opens'),
                ],
            ],
            'permanent_childcare_end_before_closes' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'childcare' => (object)['start' => '08:00', 'end' => '16:00'],
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/childcare/end', 'childcare.end must be after closes'),
                ],
            ],
            'single_subEvent_bookingInfo_phone_wrong_type' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T17:00:30+01:00',
                            'endDate' => '2021-01-01T20:00:00+01:00',
                            'bookingInfo' => (object)[
                                'phone' => 123,
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/bookingInfo/phone', 'The data (integer) must match the type: string'),
                ],
            ],
            'single_subEvent_bookingInfo_email_invalid' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T17:00:30+01:00',
                            'endDate' => '2021-01-01T20:00:00+01:00',
                            'bookingInfo' => (object)[
                                'email' => '@publiq.be',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/bookingInfo/email', 'The data must match the \'email\' format'),
                ],
            ],
            'single_subEvent_bookingInfo_url_invalid' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T17:00:30+01:00',
                            'endDate' => '2021-01-01T20:00:00+01:00',
                            'bookingInfo' => (object)[
                                'url' => 'www.publiq.be',
                                'urlLabel' => (object)['nl' => 'Reserveer'],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/bookingInfo/url', 'The data must match the \'uri\' format'),
                ],
            ],
            'single_subEvent_bookingInfo_url_without_urlLabel' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2021-01-01T17:00:30+01:00',
                            'endDate' => '2021-01-01T20:00:00+01:00',
                            'bookingInfo' => (object)[
                                'url' => 'https://www.publiq.be',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/bookingInfo', '\'urlLabel\' property is required by \'url\' property'),
                ],
            ],
            'periodic_closed_day_endDate_before_startDate' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-24',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursClosedDays/0/endDate', 'endDate should not be before startDate'),
                ],
            ],
            'periodic_closed_day_before_calendar_start' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-03-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01',
                            'endDate' => '2024-01-01',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursClosedDays/0/startDate', 'the start date of a closed day should not be before the calendar start date'),
                ],
            ],
            'periodic_closed_day_after_calendar_end' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-01-01',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursClosedDays/0/endDate', 'the end date of a closed day should not be after the calendar end date'),
                ],
            ],
            'periodic_closed_day_invalid_date_format' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '25-12-2024',
                            'endDate' => '25-12-2024',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursClosedDays/0/startDate', 'The data must match the \'date\' format'),
                    new SchemaError('/openingHoursClosedDays/0/endDate', 'The data must match the \'date\' format'),
                ],
            ],
            'periodic_adjusted_opening_hours_endDate_before_startDate' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-26',
                            'endDate' => '2026-12-21',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/endDate', 'startDate should not be later than endDate'),
                ],
            ],
            'periodic_adjusted_opening_hours_before_calendar_start' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-03-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-01-15',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/startDate', 'the start date of adjusted opening hours should not be before the calendar start date'),
                ],
            ],
            'periodic_adjusted_opening_hours_after_calendar_end' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-11-30T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/endDate', 'the end date of adjusted opening hours should not be after the calendar end date'),
                ],
            ],
            'periodic_adjusted_opening_hours_overlapping_entries' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-25',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)[
                                    'opens' => '14:00',
                                    'closes' => '16:00',
                                    'dayOfWeek' => ['saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/1/startDate', 'adjusted opening hours entries must not overlap'),
                ],
            ],
            'periodic_adjusted_opening_hours_missing_required_fields' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0', 'The required properties (endDate, openingHours) are missing'),
                ],
            ],
            'periodic_adjusted_opening_hours_invalid_opening_hours_time' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '25:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/openingHours/0/opens', 'The string should match pattern: ^([01]?\d|2[0-3]):[0-5]\d$'),
                ],
            ],
            'periodic_adjusted_opening_hours_invalid_closes_time' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '25:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/openingHours/0/closes', 'The string should match pattern: ^([01]?\d|2[0-3]):[0-5]\d$'),
                ],
            ],
            'periodic_adjusted_opening_hours_description_too_long' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'description' => (object)['nl' => str_repeat('a', 1001)],
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/0/description/nl', 'Maximum string length is 1000, found 1001'),
                ],
            ],
            'permanent_adjusted_opening_hours_overlapping_entries' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => ['monday'],
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-24',
                            'endDate' => '2026-12-30',
                            'openingHours' => [
                                (object)[
                                    'opens' => '14:00',
                                    'closes' => '16:00',
                                    'dayOfWeek' => ['saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHoursAdjustedDays/1/startDate', 'adjusted opening hours entries must not overlap'),
                ],
            ],
            'single_overnight_wrong_type_string' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-07-01T09:00:00+02:00',
                            'endDate' => '2026-07-05T17:00:00+02:00',
                            'overnight' => 'yes',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/overnight', 'The data (string) must match the type: boolean'),
                ],
            ],
            'single_overnight_wrong_type_integer' => [
                'data' => (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-07-01T09:00:00+02:00',
                            'endDate' => '2026-07-05T17:00:00+02:00',
                            'overnight' => 1,
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/subEvent/0/overnight', 'The data (integer) must match the type: boolean'),
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'status' => (object)[
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object)['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object)['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    (new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours()
                    ))->withStatus(
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T14:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object)[
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
                    new PeriodicCalendar(
                        new DateRange(
                            DateTimeFactory::fromAtom('2021-01-01T14:00:30+01:00'),
                            DateTimeFactory::fromAtom('2021-01-01T17:00:30+01:00')
                        ),
                        new OpeningHours(
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
                            )
                        )
                    )
                ),
            ],
            'permanent' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
                    new PermanentCalendar(new OpeningHours())
                ),
            ],
            'permanent_with_status_and_bookingAvailability' => [
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'status' => (object)[
                        'type' => 'TemporarilyUnavailable',
                        'reason' => (object)['nl' => 'Covid'],
                    ],
                    'bookingAvailability' => (object)['type' => 'Unavailable'],
                ],
                'expected_command' => new UpdateCalendar(
                    self::PLACE_ID,
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
                'data' => (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [
                        (object)[
                            'opens' => '10:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'monday',
                                'wednesday',
                            ],
                        ],
                        (object)[
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
                    new PermanentCalendar(
                        new OpeningHours(
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
                        )
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPlaceDataProvider
     */
    public function it_throws_an_api_problem_when_given_invalid_place_data(array|object $data, array $expectedSchemaErrors): void
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
                'data' => (object)[],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (calendarType) are missing'),
                ],
            ],
            'calendar_type_single' => [
                'data' => (object)[
                    'calendarType' => 'single',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/calendarType', 'The data should match one item from enum'),
                ],
            ],
            'calendar_type_multiple' => [
                'data' => (object)[
                    'calendarType' => 'multiple',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/calendarType', 'The data should match one item from enum'),
                ],
            ],
            'periodic_no_startDate_and_endDate' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (startDate, endDate) are missing'),
                ],
            ],
            'periodic_invalid_startDate_and_endDate' => [
                'data' => (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T10:00:30+01:00',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/endDate', 'endDate should not be before startDate'),
                ],
            ],
            'periodic_invalid_openingHours_type' => [
                'data' => (object)[
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
                'data' => (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0', 'The required properties (opens, closes, dayOfWeek) are missing'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_fields' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
                            'opens' => 10,
                            'closes' => 'foo',
                            'dayOfWeek' => 'Monday',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/openingHours/0/opens', 'The data (integer) must match the type: string'),
                    new SchemaError('/openingHours/0/closes', 'The string should match pattern: ^([01]?\d|2[0-3]):[0-5]\d$'),
                    new SchemaError('/openingHours/0/dayOfWeek', 'The data (string) must match the type: array'),
                ],
            ],
            'periodic_invalid_openingHours_item_invalid_dayOfWeek' => [
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
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
                'data' => (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2021-01-01T17:00:30+01:00',
                    'endDate' => '2021-01-01T17:00:30+01:00',
                    'openingHours' => [
                        (object)[
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
    public function it_maps_domain_invalid_argument_exception_to_400(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('dispatch')->willThrowException(new InvalidArgumentException('overnight is only allowed when the event has term 0.57.0.0.0'));

        $handler = new UpdateCalendarRequestHandler($commandBus);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail('overnight is only allowed when the event has term 0.57.0.0.0'),
            fn () => $handler->handle(
                (new Psr7RequestBuilder())
                    ->withJsonBodyFromObject((object)[
                        'calendarType' => 'single',
                        'subEvent' => [
                            (object)[
                                'startDate' => '2026-07-01T09:00:00+02:00',
                                'endDate' => '2026-07-05T17:00:00+02:00',
                                'overnight' => true,
                            ],
                        ],
                    ])
                    ->withRouteParameter('offerType', 'events')
                    ->withRouteParameter('offerId', self::EVENT_ID)
                    ->build('PUT')
            )
        );
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
