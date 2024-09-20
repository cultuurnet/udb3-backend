<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

final class RemoveLabelHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): RemoveLabelHandler
    {
        return new RemoveLabelHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_should_remove_labels_previously_added_with_the_same_visibility(): void
    {
        $id = '4c6d4bb8-702b-49f1-b0ca-e51eb09a1c19';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, 'foo', true),
                ]
            )
            ->when(new RemoveLabel($id, 'foo'))
            ->then([new LabelRemoved($id, 'foo', true)]);
    }

    /**
     * @test
     */
    public function it_should_remove_labels_previously_added_even_if_the_visibility_is_different(): void
    {
        $id = '4c6d4bb8-702b-49f1-b0ca-e51eb09a1c19';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, 'foo', true),
                ]
            )
            ->when(new RemoveLabel($id, 'foo'))
            ->then([new LabelRemoved($id, 'foo', true)]);
    }

    /**
     * @test
     */
    public function it_should_not_remove_labels_that_are_not_on_the_offer(): void
    {
        $id = '4c6d4bb8-702b-49f1-b0ca-e51eb09a1c19';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(new RemoveLabel($id, 'foo'))
            ->then([]);
    }

    private function eventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
