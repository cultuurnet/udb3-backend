<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;

class UpdateSubEventStatusHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ) {
        $repository = new EventRepository(
            $eventStore,
            $eventBus
        );

        return new UpdateSubEventsStatusHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_handle_update_sub_event_status_for_single_sub_event(): void
    {
        $id = '1';

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');

        $initialTimestamps = [new Timestamp($startDate, $endDate)];
        $expectedTimestamps = [new Timestamp($startDate, $endDate, new Status(StatusType::unavailable(), []))];

        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);
        $expectedCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []);

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(0, new Status(StatusType::unavailable(), []));

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_will_handle_update_sub_event_status_for_multiple_sub_events(): void
    {
        $id = '1';

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');

        $initialTimestamps = [
            new Timestamp($startDate, $endDate),
            new Timestamp($startDate, $endDate),
            new Timestamp($startDate, $endDate),
        ];
        $expectedTimestamps = [
            new Timestamp($startDate, $endDate, new Status(StatusType::available(), [])),
            new Timestamp($startDate, $endDate, new Status(StatusType::unavailable(), [])),
            new Timestamp($startDate, $endDate, new Status(StatusType::temporarilyUnavailable(), [])),
        ];

        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);
        $expectedCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []);

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(1, new Status(StatusType::unavailable(), []));
        $command = $command->withUpdatedStatus(2, new Status(StatusType::temporarilyUnavailable(), []));

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialCalendar)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_will_handle_update_sub_event_status_for_multiple_sub_events_after_major_info_update(): void
    {
        $id = '1';

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');

        $updatedStartDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-25');
        $updatedEndDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-25');

        $initialTimestamps = [
            new Timestamp($startDate, $endDate),
            new Timestamp($startDate, $endDate),
            new Timestamp($startDate, $endDate),
        ];
        $updatedTimestamps = [
            new Timestamp($updatedStartDate, $updatedEndDate),
            new Timestamp($updatedStartDate, $updatedEndDate),
            new Timestamp($updatedStartDate, $updatedEndDate),
        ];
        $expectedTimestamps = [
            new Timestamp($updatedStartDate, $updatedEndDate, new Status(StatusType::available(), [])),
            new Timestamp($updatedStartDate, $updatedEndDate, new Status(StatusType::unavailable(), [])),
            new Timestamp($updatedStartDate, $updatedEndDate, new Status(StatusType::temporarilyUnavailable(), [])),
        ];

        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $endDate, $initialTimestamps);
        $updatedCalendar = new Calendar(CalendarType::SINGLE(), $updatedStartDate, $updatedEndDate, $updatedTimestamps);
        $expectedCalendar = new Calendar(
            CalendarType::SINGLE(),
            $updatedStartDate,
            $updatedEndDate,
            $expectedTimestamps
        );

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(1, new Status(StatusType::unavailable(), []));
        $command = $command->withUpdatedStatus(2, new Status(StatusType::temporarilyUnavailable(), []));

        $expectedEvent = new CalendarUpdated(
            $id,
            $expectedCalendar
        );

        $eventCreated = $this->getEventCreated($id, $initialCalendar);

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $eventCreated,
                    new MajorInfoUpdated(
                        $eventCreated->getEventId(),
                        $eventCreated->getTitle(),
                        $eventCreated->getEventType(),
                        $eventCreated->getLocation(),
                        $updatedCalendar
                    ),
                ]
            )
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @param string $id
     * @param Calendar $calendar
     * @return EventCreated
     */
    private function getEventCreated(string $id, Calendar $calendar): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            $calendar
        );
    }
}
