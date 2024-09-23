<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Calendar\Timestamp;

class UpdateStatusHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        $repository = new OfferRepository(
            new EventRepository($eventStore, $eventBus),
            new PlaceRepository($eventStore, $eventBus)
        );

        return new UpdateStatusHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_handle_update_status_for_permanent_event(): void
    {
        $id = '1';
        $initialCalendar = new Calendar(CalendarType::PERMANENT());

        $newStatus = new Status(StatusType::temporarilyUnavailable(), []);
        $expectedCalendar = (new Calendar(CalendarType::PERMANENT()))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

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
    public function it_will_update_status_of_sub_events(): void
    {
        $id = '1';
        $startDate = DateTimeFactory::fromFormat('Y-m-d', '2020-12-24');
        $endDate = DateTimeFactory::fromFormat('Y-m-d', '2020-12-24');

        $initialTimestamps = [new Timestamp($startDate, $endDate)];
        $initialCalendar = new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $initialTimestamps);

        $newStatus = new Status(StatusType::unavailable(), []);

        $expectedTimestamps = [new Timestamp($startDate, $endDate, new Status(StatusType::unavailable(), []))];
        $expectedCalendar = (new Calendar(CalendarType::SINGLE(), $startDate, $startDate, $expectedTimestamps, []))->withStatus($newStatus);

        $command = new UpdateStatus($id, $newStatus);

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

    private function getEventCreated(string $id, Calendar $calendar): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            $calendar
        );
    }
}
