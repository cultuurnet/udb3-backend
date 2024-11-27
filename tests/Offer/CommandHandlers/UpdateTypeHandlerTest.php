<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryNotFound;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

class UpdateTypeHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): UpdateTypeHandler
    {
        return new UpdateTypeHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_update_type_with_valid_eventtype_category_id(): void
    {
        $id = '1';

        $command = new UpdateType($id, '0.5.0.0.0');

        $expectedEvent = new TypeUpdated($id, new EventType('0.5.0.0.0', 'Festival'));

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([$expectedEvent]);
    }

    /**
     * @test
     */
    public function it_does_not_record_an_event_if_the_type_remains_the_same(): void
    {
        $id = '1';

        $command = new UpdateType($id, '0.50.4.0.0');

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([]);
    }

    /**
     * @test
     */
    public function it_throws_if_the_type_id_does_not_exist_or_is_not_an_eventtype_for_the_expected_offer_type(): void
    {
        $id = '1';

        $command = new UpdateType($id, 'foobar');

        $this->expectException(CategoryNotFound::class);

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->getEventCreated($id)])
            ->when($command)
            ->then([]);
    }

    private function getEventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::permanent())
        );
    }
}
