<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\DescriptionDeleted;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionDeleted;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Title;

class DeleteDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    public const OFFER_ID = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

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
     * @dataProvider deleteDescriptionProvider
     *
     */
    public function it_handles_delete_of_a_description(
        EventCreated $offer,
        DescriptionTranslated $description,
        AbstractDescriptionDeleted $descriptionDeleted
    ): void {
        $offerId = self::OFFER_ID;

        $this->scenario
            ->withAggregateId($offerId)
            ->given(
                [
                    $offer,
                    $description,
                ]
            )
            ->when(new DeleteDescription(self::OFFER_ID, new Language('nl')))
            ->then(
                [
                    $descriptionDeleted,
                ]
            );
    }

    public function deleteDescriptionProvider(): array
    {
        return [
            [
                $this->getEventCreated(self::OFFER_ID),
                new DescriptionTranslated(
                    'id',
                    LegacyLanguage::fromUdb3ModelLanguage(new Language('nl')),
                    new Description('test')
                ),
                new DescriptionDeleted(self::OFFER_ID, new Language('nl')),
            ],
        ];
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new LegacyLanguage('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
