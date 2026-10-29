<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Organizer\Commands\UpdateImageAiGenerated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateImageAiGeneratedHandlerTest extends CommandHandlerScenarioTestCase
{
    private const ORGANIZER_ID = '5e360b25-fd85-4dac-acf4-0571e0b57dce';
    private const IMAGE_ID = 'cf539408-bba9-4e77-9f85-72019013db37';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateImageAiGeneratedHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_updates_ai_generated_of_an_image(): void
    {
        $this->scenario
            ->withAggregateId(self::ORGANIZER_ID)
            ->given([
                $this->organizerCreatedWithUniqueWebsite(),
                $this->imageAdded(null),
            ])
            ->when(new UpdateImageAiGenerated(self::ORGANIZER_ID, new Uuid(self::IMAGE_ID), true))
            ->then([
                new ImageUpdated(
                    self::ORGANIZER_ID,
                    self::IMAGE_ID,
                    'en',
                    'Description',
                    'Copyright holder',
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
            ->withAggregateId(self::ORGANIZER_ID)
            ->given([
                $this->organizerCreatedWithUniqueWebsite(),
                $this->imageAdded(true),
            ])
            ->when(new UpdateImageAiGenerated(self::ORGANIZER_ID, new Uuid(self::IMAGE_ID), true))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_images_that_are_not_linked(): void
    {
        $this->scenario
            ->withAggregateId(self::ORGANIZER_ID)
            ->given([
                $this->organizerCreatedWithUniqueWebsite(),
                $this->imageAdded(null),
            ])
            ->when(new UpdateImageAiGenerated(self::ORGANIZER_ID, new Uuid('e717473d-609d-4bf1-a008-c935c8836335'), true))
            ->then([]);
    }

    private function organizerCreatedWithUniqueWebsite(): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            self::ORGANIZER_ID,
            'en',
            'https://www.publiq.be',
            'publiq'
        );
    }

    private function imageAdded(?bool $isAiGenerated): ImageAdded
    {
        return new ImageAdded(
            self::ORGANIZER_ID,
            self::IMAGE_ID,
            'en',
            'Description',
            'Copyright holder',
            $isAiGenerated
        );
    }
}
