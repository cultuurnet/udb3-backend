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
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\EventStatus;
use CultuurNet\UDB3\Event\ValueObjects\EventStatusType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
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
        $expectedTimestamps = [new Timestamp($startDate, $endDate, new EventStatus(EventStatusType::cancelled(), []))];

        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);
        $expectedCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []);

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(0, new EventStatus(EventStatusType::cancelled(), []));

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
            new Timestamp($startDate, $endDate, new EventStatus(EventStatusType::scheduled(), [])),
            new Timestamp($startDate, $endDate, new EventStatus(EventStatusType::cancelled(), [])),
            new Timestamp($startDate, $endDate, new EventStatus(EventStatusType::postponed(), [])),
        ];

        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);
        $expectedCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []);

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(1, new EventStatus(EventStatusType::cancelled(), []));
        $command = $command->withUpdatedStatus(2, new EventStatus(EventStatusType::postponed(), []));

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
