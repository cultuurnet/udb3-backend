<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\ImportImages;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

class ImportImagesHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new ImportImagesHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_importing_a_new_image_and_updating_an_existing_one_and_deleting_an_existing_one(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreatedWithUniqueWebsite($id),
                    new ImageAdded(
                        $id,
                        '2361bfcb-a786-44a2-8e85-87e194c7b12b',
                        'nl',
                        'beschrijving1',
                        'copyrightholder1',
                    ),
                    new ImageAdded(
                        $id,
                        'd3d085db-dbd7-4663-b3c3-56bf9a54a025',
                        'nl',
                        'beschrijving2',
                        'copyrightholder2',
                    ),
                ]
            )
            ->when(
                new ImportImages(
                    $id,
                    new Images(
                        new Image(
                            new UUID('d3d085db-dbd7-4663-b3c3-56bf9a54a025'),
                            new Language('en'),
                            new Description('beschrijving2 - updated'),
                            new CopyrightHolder('copyrightholder2 - updated')
                        ),
                        new Image(
                            new UUID('cf539408-bba9-4e77-9f85-72019013db37'),
                            new Language('nl'),
                            new Description('beschrijving3'),
                            new CopyrightHolder('copyrightholder3')
                        )
                    )
                )
            )
            ->then([
                new ImageAdded(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'nl',
                    'beschrijving3',
                    'copyrightholder3'
                ),
                new ImageUpdated(
                    $id,
                    'd3d085db-dbd7-4663-b3c3-56bf9a54a025',
                    'en',
                    'beschrijving2 - updated',
                    'copyrightholder2 - updated'
                ),
                new ImageRemoved(
                    $id,
                    '2361bfcb-a786-44a2-8e85-87e194c7b12b'
                ),
            ]);
    }

    private function organizerCreatedWithUniqueWebsite(string $id): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            $id,
            'nl',
            'https://www.publiq.be',
            'publiq'
        );
    }
}
