<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\CalendarTypeNotSupported;
use DateTimeImmutable;

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
            'Permanent Event',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
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
            'Periodic Event',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PeriodicCalendar(
                new DateRange(
                    new DateTimeImmutable('2020-01-01 10:00:00'),
                    new DateTimeImmutable('2020-01-01 12:00:00')
                ),
                new OpeningHours()
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
            'Single Event',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new SingleSubEventCalendar(
                SubEvent::createAvailable(
                    new DateRange(
                        new DateTimeImmutable('2020-01-01 10:00:00'),
                        new DateTimeImmutable('2020-01-01 12:00:00')
                    )
                )
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
            'Update start date on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))->withStartDate(new DateTimeImmutable('2019-12-29 10:00:00'))
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-29 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
            ],
            'Update start date and end date on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))
                        ->withStartDate(new DateTimeImmutable('2019-12-29 10:00:00'))
                        ->withEndDate(new DateTimeImmutable('2019-12-29 12:00:00'))
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-29 10:00:00'),
                                    new DateTimeImmutable('2019-12-29 12:00:00')
                                )
                            )
                        )
                    )
                ),
            ],
            'Update start date on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withStartDate(new DateTimeImmutable('2019-12-31 10:00:00')),
                    (new SubEventUpdate(1))->withStartDate(new DateTimeImmutable('2019-12-29 10:00:00'))
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-31 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-29 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
            ],
            'Update start date and end date on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))
                        ->withStartDate(new DateTimeImmutable('2019-12-31 10:00:00'))
                        ->withEndDate(new DateTimeImmutable('2019-12-31 12:00:00')),
                    (new SubEventUpdate(1))
                        ->withStartDate(new DateTimeImmutable('2019-12-29 10:00:00'))
                        ->withEndDate(new DateTimeImmutable('2019-12-29 12:00:00'))
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-31 10:00:00'),
                                    new DateTimeImmutable('2019-12-31 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2019-12-29 10:00:00'),
                                    new DateTimeImmutable('2019-12-29 12:00:00')
                                )
                            )
                        )
                    )
                ),
            ],
            'Update status on 1 sub event' => [
               new EventCreated(
                   '1',
                   new Language('nl'),
                   'Multiple Event',
                   new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                   new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                   new MultipleSubEventsCalendar(
                       new SubEvents(
                           SubEvent::createAvailable(
                               new DateRange(
                                   new DateTimeImmutable('2020-01-01 10:00:00'),
                                   new DateTimeImmutable('2020-01-01 12:00:00')
                               )
                           ),
                           SubEvent::createAvailable(
                               new DateRange(
                                   new DateTimeImmutable('2020-01-03 10:00:00'),
                                   new DateTimeImmutable('2020-01-03 12:00:00')
                               )
                           )
                       )
                   )
               ),
               new UpdateSubEvents(
                   '1',
                   (new SubEventUpdate(1))->withStatus(
                       new Status(
                           StatusType::Unavailable(),
                           new TranslatedStatusReason(
                               new Language('nl'),
                               new StatusReason('Niet beschikbaar')
                           )
                       )
                   )
               ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::Unavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Niet beschikbaar')
                                    )
                                )
                            ),
                        )
                    )
                ),
           ],
            'Update status on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withStatus(
                        new Status(
                            StatusType::Unavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Niet beschikbaar')
                            )
                        )
                    ),
                    (new SubEventUpdate(1))->withStatus(
                        new Status(
                            StatusType::TemporarilyUnavailable(),
                            new TranslatedStatusReason(
                                new Language('nl'),
                                new StatusReason('Tijdelijk niet beschikbaar')
                            )
                        )
                    )
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::Unavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Niet beschikbaar')
                                    )
                                )
                            ),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::TemporarilyUnavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Tijdelijk niet beschikbaar')
                                    )
                                )
                            ),
                        )
                    )
                ),
            ],
            'Update booking availability on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))->withBookingAvailability(
                        new BookingAvailability(
                            BookingAvailabilityType::Unavailable()
                        )
                    )
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                        )
                    )
                ),
            ],
            'Update booking availability on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))->withBookingAvailability(
                        new BookingAvailability(
                            BookingAvailabilityType::Unavailable()
                        )
                    ),
                    (new SubEventUpdate(1))->withBookingAvailability(
                        new BookingAvailability(
                            BookingAvailabilityType::Unavailable()
                        )
                    )
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withBookingAvailability(BookingAvailability::Unavailable()),
                        )
                    )
                ),
            ],
            'Update status and booking on 1 sub event' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(1))
                        ->withStatus(
                            new Status(
                                StatusType::Unavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Niet beschikbaar')
                                )
                            )
                        )
                        ->withBookingAvailability(
                            new BookingAvailability(
                                BookingAvailabilityType::Unavailable()
                            )
                        )
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::Unavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Niet beschikbaar')
                                    )
                                )
                            )->withBookingAvailability(BookingAvailability::Unavailable()),
                        )
                    )
                ),
            ],
            'Update booking and status on 2 sub events' => [
                new EventCreated(
                    '1',
                    new Language('nl'),
                    'Multiple Event',
                    new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                    new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ),
                            SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            )
                        )
                    )
                ),
                new UpdateSubEvents(
                    '1',
                    (new SubEventUpdate(0))
                        ->withStatus(
                            new Status(
                                StatusType::Unavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Niet beschikbaar')
                                )
                            )
                        )
                        ->withBookingAvailability(
                            new BookingAvailability(
                                BookingAvailabilityType::Unavailable()
                            )
                        ),
                    (new SubEventUpdate(1))
                        ->withStatus(
                            new Status(
                                StatusType::TemporarilyUnavailable(),
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Tijdelijk niet beschikbaar')
                                )
                            )
                        )
                        ->withBookingAvailability(
                            new BookingAvailability(
                                BookingAvailabilityType::Unavailable()
                            )
                        )
                ),
                new CalendarUpdated(
                    '1',
                    new MultipleSubEventsCalendar(
                        new SubEvents(
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-01 10:00:00'),
                                    new DateTimeImmutable('2020-01-01 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::Unavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Niet beschikbaar')
                                    )
                                )
                            )->withBookingAvailability(BookingAvailability::Unavailable()),
                            (SubEvent::createAvailable(
                                new DateRange(
                                    new DateTimeImmutable('2020-01-03 10:00:00'),
                                    new DateTimeImmutable('2020-01-03 12:00:00')
                                )
                            ))->withStatus(
                                new Status(
                                    StatusType::TemporarilyUnavailable(),
                                    new TranslatedStatusReason(
                                        new Language('nl'),
                                        new StatusReason('Tijdelijk niet beschikbaar')
                                    )
                                )
                            )->withBookingAvailability(BookingAvailability::Unavailable())
                        )
                    )
                ),
            ],
        ];
    }
}
