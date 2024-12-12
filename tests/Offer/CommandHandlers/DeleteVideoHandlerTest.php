<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoDeleted;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\DeleteVideo;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

final class DeleteVideoHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus   $eventBus
    ): CommandHandler {
        $repository = new OfferRepository(
            new EventRepository($eventStore, $eventBus),
            new PlaceRepository($eventStore, $eventBus)
        );

        return new DeleteVideoHandler($repository);
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_video_from_an_event(): void
    {
        $eventId = '208dbe98-ffaa-41cb-9ada-7ec8e0651f48';

        $videoId = '91c75325-3830-4000-b580-5778b2de4548';
        $video = new Video(
            $videoId,
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        $this->scenario
            ->withAggregateId($eventId)
            ->given([
                $this->getEventCreated($eventId),
                new VideoAdded($eventId, $video),
            ])
            ->when(new DeleteVideo($eventId, $videoId))
            ->then([
                new VideoDeleted($eventId, $videoId),
            ]);
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
