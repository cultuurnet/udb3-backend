<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\Commands\UpdateDeparturePlaces;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\DeparturePlacesUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\IncompatibleAudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;

final class UpdateDeparturePlacesHandlerTest extends CommandHandlerScenarioTestCase
{
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateDeparturePlacesHandler(new EventRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_departure_places_on_children_only_event(): void
    {
        $departurePlaces = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
            new Url('https://io.uitdatabank.be/places/1b2c3d4e-5f6a-7b8c-9d0e-1f2a3b4c5d6e'),
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new AudienceUpdated(self::EVENT_ID, AudienceType::childrenOnly()),
            ])
            ->when(new UpdateDeparturePlaces(self::EVENT_ID, $departurePlaces))
            ->then([new DeparturePlacesUpdated(self::EVENT_ID, $departurePlaces)]);
    }

    /**
     * @test
     */
    public function it_replaces_existing_departure_places(): void
    {
        $initial = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
        );

        $updated = new Urls(
            new Url('https://io.uitdatabank.be/places/1b2c3d4e-5f6a-7b8c-9d0e-1f2a3b4c5d6e'),
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new AudienceUpdated(self::EVENT_ID, AudienceType::childrenOnly()),
                new DeparturePlacesUpdated(self::EVENT_ID, $initial),
            ])
            ->when(new UpdateDeparturePlaces(self::EVENT_ID, $updated))
            ->then([new DeparturePlacesUpdated(self::EVENT_ID, $updated)]);
    }

    /**
     * @test
     */
    public function it_ignores_updating_when_departure_places_are_the_same(): void
    {
        $departurePlaces = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new AudienceUpdated(self::EVENT_ID, AudienceType::childrenOnly()),
                new DeparturePlacesUpdated(self::EVENT_ID, $departurePlaces),
            ])
            ->when(new UpdateDeparturePlaces(self::EVENT_ID, $departurePlaces))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_clearing_all_departure_places(): void
    {
        $departurePlaces = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([
                $this->getEventCreated(),
                new AudienceUpdated(self::EVENT_ID, AudienceType::childrenOnly()),
                new DeparturePlacesUpdated(self::EVENT_ID, $departurePlaces),
            ])
            ->when(new UpdateDeparturePlaces(self::EVENT_ID, new Urls()))
            ->then([new DeparturePlacesUpdated(self::EVENT_ID, new Urls())]);
    }

    /**
     * @test
     */
    public function it_throws_when_audience_type_is_not_children_only(): void
    {
        $departurePlaces = new Urls(
            new Url('https://io.uitdatabank.be/places/5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f'),
        );

        $this->expectException(IncompatibleAudienceType::class);
        $this->expectExceptionMessage(
            'Departure places can only be set on events with audienceType "childrenOnly". Event: ' . self::EVENT_ID
        );

        $this->scenario
            ->withAggregateId(self::EVENT_ID)
            ->given([$this->getEventCreated()])
            ->when(new UpdateDeparturePlaces(self::EVENT_ID, $departurePlaces))
            ->then([]);
    }

    private function getEventCreated(): EventCreated
    {
        return new EventCreated(
            self::EVENT_ID,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
