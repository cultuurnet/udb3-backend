<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use DateTimeImmutable;

class UpdateCalendarHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        return new UpdateCalendarHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_updates_the_calendar_on_the_given_offer(): void
    {
        $id = '1ba6bafc-4368-4947-b3a4-48ea71bfe1a4';

        $initialCalendar = new Calendar(CalendarType::PERMANENT());

        $calendar = new Calendar(
            CalendarType::PERIODIC(),
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id, $initialCalendar)])
            ->when(new UpdateCalendar($id, $initialCalendar))
            ->then([])
            ->when(new UpdateCalendar($id, $calendar))
            ->then([new CalendarUpdated($id, $calendar)]);
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
