<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Organizer\Commands\UpdateMainImage;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\MainImageUpdated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateMainImageHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateMainImageHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_setting_main_image(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                new ImageAdded(
                    $id,
                    '1c0e2f0c-fa9c-40c4-9334-8fe97bc28896',
                    'nl',
                    'Bescrhijving',
                    'madewithlove'
                ),
                new ImageAdded(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'en',
                    'Description',
                    'publiq'
                ),
            ])
            ->when(
                new UpdateMainImage($id, new Uuid('cf539408-bba9-4e77-9f85-72019013db37'))
            )
            ->then([
                new MainImageUpdated($id, 'cf539408-bba9-4e77-9f85-72019013db37'),
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
