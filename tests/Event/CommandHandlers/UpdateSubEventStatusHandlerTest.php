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
    public function it_will_handle_update_sub_event_status(): void
    {
        $id = '1';

        $startDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');
        $endDate = DateTimeImmutable::createFromFormat('Y-m-d', '2020-11-24');

        $initialTimestamps = [new Timestamp($startDate, $endDate)];
        $expectedTimestamps = [new Timestamp($startDate, $endDate, new EventStatus(EventStatusType::cancelled(), []))];

        $command = new UpdateSubEventsStatus($id);
        $command = $command->withUpdatedStatus(0, new EventStatus(EventStatusType::cancelled(), []));
        $expectedEvent = new CalendarUpdated(
            $id,
            new Calendar(CalendarType::SINGLE(), $startDate, $endDate, $expectedTimestamps)
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialTimestamps)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @param string $id
     * @param Timestamp[] $timestamps
     * @return EventCreated
     */
    private function getEventCreated(string $id, array $timestamps): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::SINGLE(), null, null, $timestamps, [])
        );
    }
}
