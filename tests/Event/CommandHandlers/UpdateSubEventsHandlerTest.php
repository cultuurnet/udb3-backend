<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTime;

final class UpdateSubEventsHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateSubEventsHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_throws_on_update_events_on_permanent_calendar(): void
    {
        $permanentEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            new Title('Permanent Event'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$permanentEventCreated])
            ->when(new UpdateSubEvents('1', new SubEventUpdate(1)))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_on_update_events_on_periodic_calendar(): void
    {
        $periodicEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            new Title('Periodic Event'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::PERIODIC(),
                new DateTime('2020-01-01 10:00:00'),
                new DateTime('2020-01-01 12:00:00')
            )
        );

        $this->expectException(CalendarTypeNotSupported::class);

        $this->scenario
            ->withAggregateId('1')
            ->given([$periodicEventCreated])
            ->when(new UpdateSubEvents('1', new SubEventUpdate(1)))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_same_calendar(): void
    {
        $singleEventCreated = new EventCreated(
            '1',
            new Language('nl'),
            new Title('Single Event'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::SINGLE(),
                null,
                null,
                [
                    new Timestamp(
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-01 12:00:00')
                    ),
                ]
            )
        );

        $this->scenario
            ->withAggregateId('1')
            ->given([$singleEventCreated])
            ->when(new UpdateSubEvents('1', new SubEventUpdate(1)))
            ->then([]);
    }

    /**
     * @test
     * @dataProvider calendarProvider
     */
    public function it_can_update_status_on_one_sub_event(
        EventCreated $eventCreated,
        UpdateSubEvents $updateSubEvents,
        CalendarUpdated $calendarUpdated
    ): void {
        $this->scenario
            ->withAggregateId('1')
            ->given([$eventCreated])
            ->when($updateSubEvents)
            ->then([$calendarUpdated]);
    }

    public function calendarProvider(): array
    {
        return [
            'Update status on 1 sub event' => [
               new EventCreated(
                   '1',
                   new Language('nl'),
                   new Title('Multiple Event'),
                   new EventType('0.50.4.0.0', 'concert'),
                   new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                   new Calendar(
                       CalendarType::MULTIPLE(),
                       null,
                       null,
                       [
                           new Timestamp(
                               new DateTime('2020-01-01 10:00:00'),
                               new DateTime('2020-01-01 12:00:00')
                           ),
                           new Timestamp(
                               new DateTime('2020-01-03 10:00:00'),
                               new DateTime('2020-01-03 12:00:00')
                           ),
                       ]
                   )
               ),
               new UpdateSubEvents(
                   '1',
                   (new SubEventUpdate(1))->withStatus(
                       new Status(
                           StatusType::unavailable(),
                           [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                       )
                   )
               ),
               new CalendarUpdated(
                   '1',
                   new Calendar(
                       CalendarType::MULTIPLE(),
                       new DateTime('2020-01-01 10:00:00'),
                       new DateTime('2020-01-03 12:00:00'),
                       [
                           new Timestamp(
                               new DateTime('2020-01-01 10:00:00'),
                               new DateTime('2020-01-01 12:00:00')
                           ),
                           (new Timestamp(
                               new DateTime('2020-01-03 10:00:00'),
                               new DateTime('2020-01-03 12:00:00')
                           ))->withStatus(
                               new Status(
                                   StatusType::unavailable(),
                                   [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                               )
                           ),
                       ]
                   )
               ),
           ],
            'Update status on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    new Title('Multiple Event'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ),
                        ]
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withStatus(
                        new Status(
                            StatusType::unavailable(),
                            [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                        )
                    ),
                    (new SubEventUpdate(1))->withStatus(
                        new Status(
                            StatusType::temporarilyUnavailable(),
                            [new StatusReason(new Language('nl'), 'Tijdelijk niet beschikbaar')]
                        )
                    )
                ),
                new CalendarUpdated(
                    '1',
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-03 12:00:00'),
                        [
                            (new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ))->withStatus(
                                new Status(
                                    StatusType::unavailable(),
                                    [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                                )
                            ),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withStatus(
                                new Status(
                                    StatusType::temporarilyUnavailable(),
                                    [new StatusReason(new Language('nl'), 'Tijdelijk niet beschikbaar')]
                                )
                            ),
                        ]
                    )
                ),
            ],
            'Update booking availability on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    new Title('Multiple Event'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ),
                        ]
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))->withBookingAvailability(BookingAvailability::unavailable())
                ),
                new CalendarUpdated(
                    '1',
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-03 12:00:00'),
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::unavailable()),
                        ]
                    )
                ),
            ],
            'Update booking availability on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    new Title('Multiple Event'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ),
                        ]
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withBookingAvailability(BookingAvailability::unavailable()),
                    (new SubEventUpdate(1))->withBookingAvailability(BookingAvailability::unavailable())
                ),
                new CalendarUpdated(
                    '1',
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-03 12:00:00'),
                        [
                            (new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::unavailable()),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withBookingAvailability(BookingAvailability::unavailable()),
                        ]
                    )
                ),
            ],
            'Update status and booking on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    new Title('Multiple Event'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ),
                        ]
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))->withStatus(
                            new Status(
                                StatusType::unavailable(),
                                [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                            )
                        )->withBookingAvailability(BookingAvailability::unavailable())
                ),
                new CalendarUpdated(
                    '1',
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-03 12:00:00'),
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withStatus(
                                new Status(
                                    StatusType::unavailable(),
                                    [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                                )
                            )->withBookingAvailability(BookingAvailability::unavailable()),
                        ]
                    )
                ),
            ],
            'Update booking and status on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    new Title('Multiple Event'),
                    new EventType('0.50.4.0.0', 'concert'),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        null,
                        null,
                        [
                            new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ),
                            new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ),
                        ]
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withStatus(
                        new Status(
                            StatusType::unavailable(),
                            [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                        )
                    )->withBookingAvailability(BookingAvailability::unavailable()),
                    (new SubEventUpdate(1))->withStatus(
                        new Status(
                            StatusType::temporarilyUnavailable(),
                            [new StatusReason(new Language('nl'), 'Tijdelijk niet beschikbaar')]
                        )
                    )->withBookingAvailability(BookingAvailability::unavailable())
                ),
                new CalendarUpdated(
                    '1',
                    new Calendar(
                        CalendarType::MULTIPLE(),
                        new DateTime('2020-01-01 10:00:00'),
                        new DateTime('2020-01-03 12:00:00'),
                        [
                            (new Timestamp(
                                new DateTime('2020-01-01 10:00:00'),
                                new DateTime('2020-01-01 12:00:00')
                            ))->withStatus(
                                new Status(
                                    StatusType::unavailable(),
                                    [new StatusReason(new Language('nl'), 'Niet beschikbaar')]
                                )
                            )->withBookingAvailability(BookingAvailability::unavailable()),
                            (new Timestamp(
                                new DateTime('2020-01-03 10:00:00'),
                                new DateTime('2020-01-03 12:00:00')
                            ))->withStatus(
                                new Status(
                                    StatusType::temporarilyUnavailable(),
                                    [new StatusReason(new Language('nl'), 'Tijdelijk niet beschikbaar')]
                                )
                            )->withBookingAvailability(BookingAvailability::unavailable()),
                        ]
                    )
                ),
            ],
        ];
    }
}
