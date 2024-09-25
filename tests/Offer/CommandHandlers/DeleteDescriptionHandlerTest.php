<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\DescriptionDeleted;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

class DeleteDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    private const OFFER_ID = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): DeleteDescriptionHandler
    {
        return new DeleteDescriptionHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     */
    public function it_handles_delete_of_a_description(): void
    {
        $this->scenario
            ->withAggregateId(self::OFFER_ID)
            ->given(
                [
                    $this->getEventCreated(self::OFFER_ID),
                    new DescriptionTranslated(
                        'id',
                        new Language('nl'),
                        new Description('test')
                    ),
                ]
            )
            ->when(new DeleteDescription(self::OFFER_ID, new Language('nl')))
            ->then(
                [
                    new DescriptionDeleted(self::OFFER_ID, new Language('nl')),
                ]
            );
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     */
    public function it_handles_delete_of_a_description_but_there_is_no_description(): void
    {
        $this->scenario
            ->withAggregateId(self::OFFER_ID)
            ->given(
                [
                    $this->getEventCreated(self::OFFER_ID),
                ]
            )
            ->when(new DeleteDescription(self::OFFER_ID, new Language('nl')))
            ->then([]);
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            'some representative title',
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
