<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\Status\UpdateSubEventStatus;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\Status\SubEventCancelled;
use CultuurNet\UDB3\Event\Events\Status\SubEventPostponed;
use CultuurNet\UDB3\Event\Events\Status\SubEventScheduled;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTime;

class UpdateSubEventStatusHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    )
    {
        return new UpdateSubEventStatusHandler(
            new EventRepository(
                $eventStore,
                $eventBus
            )
        );
    }

    private function eventCreatedWithCalendarTypeSingle(): EventCreated
    {
        return new EventCreated(
            'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
            new Language('nl'),
            new Title('Single date event'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(
                CalendarType::SINGLE(),
                new DateTime('2020-10-15T22:00:00+00:00'),
                new DateTime('2020-10-16T21:59:59+00:00'),
                [
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                ]
            )
        );
    }

    /**
     * @test
     */
    public function it_can_cancel_a_sub_event(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->eventCreatedWithCalendarTypeSingle(),
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::cancelled(),
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([
                new SubEventCancelled(
                    $eventId,
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_can_postpone_a_sub_event(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->eventCreatedWithCalendarTypeSingle(),
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::postponed(),
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([
                new SubEventPostponed(
                    $eventId,
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_can_schedule_a_sub_event(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->eventCreatedWithCalendarTypeSingle(),
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::scheduled(),
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([
                new SubEventScheduled(
                    $eventId,
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_update_events_with_calendar_type_permanent(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    new EventCreated(
                        'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                        new Language('nl'),
                        new Title('Single date event'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                        new Calendar(CalendarType::PERMANENT())
                    ),
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::cancelled(),
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_events_with_calendar_type_periodic(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    new EventCreated(
                        'd2b41f1d-598c-46af-a3a5-10e373faa6fe',
                        new Language('nl'),
                        new Title('Single date event'),
                        new EventType('0.50.4.0.0', 'concert'),
                        new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
                        new Calendar(
                            CalendarType::PERIODIC(),
                            new DateTime('2020-10-15T22:00:00+00:00'),
                            new DateTime('2020-10-16T21:59:59+00:00')
                        )
                    ),
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::cancelled(),
                    new Timestamp(
                        new DateTime('2020-10-15T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_update_events_when_timestamp_does_not_match_a_sub_event(): void
    {
        $eventId = 'd2b41f1d-598c-46af-a3a5-10e373faa6fe';

        $this->scenario
            ->withAggregateId($eventId)
            ->given(
                [
                    $this->eventCreatedWithCalendarTypeSingle()
                ]
            )
            ->when(
                new UpdateSubEventStatus(
                    $eventId,
                    Status::cancelled(),
                    new Timestamp(
                        new DateTime('2020-10-14T22:00:00+00:00'),
                        new DateTime('2020-10-16T21:59:59+00:00')
                    ),
                    'Cancel event'
                )
            )
            ->then([]);
    }
}
