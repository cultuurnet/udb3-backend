<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageUpdated as EventImageUpdated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Image\UpdateImageAiGenerated;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;

final class UpdateImageAiGeneratedHandlerTest extends CommandHandlerScenarioTestCase
{
    private const OFFER_ID = 'b26a4aef-c32e-40a4-9ac2-03272b2b73c5';
    private const IMAGE_ID = 'c263ce95-44b4-41b0-916f-ce72063b929b';
    private const OTHER_IMAGE_ID = 'e717473d-609d-4bf1-a008-c935c8836335';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateImageAiGeneratedHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            )
        );
    }

    /**
     * @test
     */
    public function it_updates_ai_generated_of_an_event_image(): void
    {
        $this->scenario
            ->withAggregateId(self::OFFER_ID)
            ->given([
                $this->eventCreated(),
                new EventImageAdded(self::OFFER_ID, $this->image(null)),
            ])
            ->when(new UpdateImageAiGenerated(self::OFFER_ID, new Uuid(self::IMAGE_ID), true))
            ->then([
                new EventImageUpdated(
                    self::OFFER_ID,
                    self::IMAGE_ID,
                    'Beschrijving',
                    'Rechtenhouder',
                    'nl',
                    true
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_does_not_update_when_ai_generated_is_unchanged(): void
    {
        $this->scenario
            ->withAggregateId(self::OFFER_ID)
            ->given([
                $this->eventCreated(),
                new EventImageAdded(self::OFFER_ID, $this->image(true)),
            ])
            ->when(new UpdateImageAiGenerated(self::OFFER_ID, new Uuid(self::IMAGE_ID), true))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_images_that_are_not_linked(): void
    {
        $this->scenario
            ->withAggregateId(self::OFFER_ID)
            ->given([
                $this->eventCreated(),
                new EventImageAdded(self::OFFER_ID, $this->image(null)),
            ])
            ->when(new UpdateImageAiGenerated(self::OFFER_ID, new Uuid(self::OTHER_IMAGE_ID), true))
            ->then([]);
    }

    private function image(?bool $isAiGenerated): Image
    {
        return new Image(
            new Uuid(self::IMAGE_ID),
            new MIMEType('image/png'),
            new Description('Beschrijving'),
            new CopyrightHolder('Rechtenhouder'),
            new Url('https://images.uitdatabank.local/' . self::IMAGE_ID . '.png'),
            new Language('nl'),
            $isAiGenerated
        );
    }

    private function eventCreated(): EventCreated
    {
        return new EventCreated(
            self::OFFER_ID,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
