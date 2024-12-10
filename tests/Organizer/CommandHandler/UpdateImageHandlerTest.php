<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateImageHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateImageHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_an_image(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                $this->imageAdded($id),
            ])
            ->when(
                (new UpdateImage(
                    $id,
                    new UUID('cf539408-bba9-4e77-9f85-72019013db37')
                ))
                    ->withLanguage(new Language('nl'))
                    ->withDescription(new Description('Beschrijving'))
                    ->withCopyrightHolder(new CopyrightHolder('Rechtenhouder'))
            )
            ->then([
                new ImageUpdated(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'nl',
                    'Beschrijving',
                    'Rechtenhouder'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_only_updates_existing_images(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                $this->imageAdded($id),
            ])
            ->when(
                (new UpdateImage(
                    $id,
                    new UUID('e717473d-609d-4bf1-a008-c935c8836335')
                ))->withLanguage(new Language('nl'))
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_only_updates_when_there_is_a_change(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                $this->imageAdded($id),
            ])
            ->when(
                (new UpdateImage(
                    $id,
                    new UUID('cf539408-bba9-4e77-9f85-72019013db37')
                ))
                    ->withLanguage(new Language('en'))
                    ->withDescription(new Description('Description'))
                    ->withCopyrightHolder(new CopyrightHolder('Copyright holder'))
            )
            ->then([]);
    }

    private function organizerCreatedWithUniqueWebsite(string $id): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            $id,
            'en',
            'https://www.publiq.be',
            'publiq'
        );
    }

    private function imageAdded(string $id): ImageAdded
    {
        return new ImageAdded(
            $id,
            'cf539408-bba9-4e77-9f85-72019013db37',
            'en',
            'Description',
            'Copyright holder'
        );
    }
}
