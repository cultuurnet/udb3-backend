<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideo;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Title;

class UpdateVideoHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        $repository = new OfferRepository(
            new EventRepository($eventStore, $eventBus),
            new PlaceRepository($eventStore, $eventBus)
        );

        return new UpdateVideoHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_handle_an_update_off_the_language(): void
    {
        $offerId = 'b26a4aef-c32e-40a4-9ac2-03272b2b73c5';
        $videoId = 'c263ce95-44b4-41b0-916f-ce72063b929b';
        $initialVideo = new Video(
            $videoId,
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        $updateVideo = (new UpdateVideo($offerId, $videoId))->withLanguage(new Language('fr'));

        $expectedEvent = new VideoUpdated(
            $offerId,
            new Video(
                $videoId,
                new Url('https://www.youtube.com/watch?v=123'),
                new Language('fr')
            )
        );

        $this->scenario
            ->withAggregateId($offerId)
            ->given([$this->getEventCreated($offerId), new VideoAdded($offerId, $initialVideo)])
            ->when($updateVideo)
            ->then([$expectedEvent]);
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
