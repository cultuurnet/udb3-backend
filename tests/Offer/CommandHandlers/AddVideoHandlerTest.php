<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\Event\Events\VideoAdded as VideoAddedToEvent;
use CultuurNet\UDB3\Place\Events\VideoAdded as VideoAddedToPlace;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Geography\Country;

class AddVideoHandlerTest extends CommandHandlerScenarioTestCase
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
    public function it_will_add_a_video_to_an_offer(): void
    {
        $id = new UUID('208dbe98-ffaa-41cb-9ada-7ec8e0651f48');
        $video = new Video(
            new UUID('91c75325-3830-4000-b580-5778b2de4548'),
            new Url('https://www.youtube.com/watch?v=123'),
            new Description('Demo youtube video'),
            new CopyrightHolder('Creative Commons')
        );

        $this->scenario
            ->withAggregateId($id->toString())
            ->given([$this->getEventCreated($id)])
            ->when(new AddVideo($id, $video))
            ->then([new VideoAddedToEvent($id, $video)]);
    }

    private function getEventCreated(UUID $id): EventCreated
    {
        return new EventCreated(
            $id->toString(),
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    private function getPlaceCreated(UUID $id): PlaceCreated
    {
        return new PlaceCreated(
            $id->toString(),
            new Language('fr'),
            new Title('some place name'),
            new EventType(
                'BtVNd33sR0WntjALVbyp3w',
                'Bioscoop'
            ),
            new Address(
                new Street('Straat 1'),
                new PostalCode('1000'),
                new Locality('Brussel'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
