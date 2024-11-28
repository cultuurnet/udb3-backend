<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

final class AddVideoHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        $repository = new OfferRepository(
            new EventRepository($eventStore, $eventBus),
            new PlaceRepository($eventStore, $eventBus)
        );

        return new AddVideoHandler($repository);
    }

    /**
     * @test
     */
    public function it_will_add_a_video_to_an_event(): void
    {
        $eventId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';
        $video = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $this->scenario
            ->withAggregateId($eventId)
            ->given([$this->getEventCreated($eventId)])
            ->when(new AddVideo($eventId, $video))
            ->then([new VideoAdded($eventId, $video)]);
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::permanent())
        );
    }
}
