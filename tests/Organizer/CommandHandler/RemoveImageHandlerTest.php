<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Organizer\Commands\RemoveImage;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class RemoveImageHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new RemoveImageHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     * @dataProvider removeImageProvider
     */
    public function it_handles_removing_an_image(
        array $given,
        RemoveImage $removeImage,
        array $then
    ): void {
        $this->scenario
            ->withAggregateId('5e360b25-fd85-4dac-acf4-0571e0b57dce')
            ->given($given)
            ->when($removeImage)
            ->then($then);
    }

    public function removeImageProvider(): array
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        return [
            'Remove an image' => [
                [
                    $this->organizerCreatedWithUniqueWebsite($id),
                    new ImageAdded(
                        $id,
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Description of the image',
                        'publiq'
                    ),
                ],
                new removeImage(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Uuid('cf539408-bba9-4e77-9f85-72019013db37')
                ),
                [
                    new ImageRemoved(
                        $id,
                        'cf539408-bba9-4e77-9f85-72019013db37',
                    ),
                ],
            ],
            'Remove an image twice' => [
                [
                    $this->organizerCreatedWithUniqueWebsite($id),
                    new ImageAdded(
                        $id,
                        'cf539408-bba9-4e77-9f85-72019013db37',
                        'nl',
                        'Description of the image',
                        'publiq'
                    ),
                    new ImageRemoved(
                        $id,
                        'cf539408-bba9-4e77-9f85-72019013db37',
                    ),
                ],
                new removeImage(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Uuid('cf539408-bba9-4e77-9f85-72019013db37')
                ),
                [
                ],
            ],
            'Removing when no image present' => [
                [
                    $this->organizerCreatedWithUniqueWebsite($id),
                ],
                new removeImage(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Uuid('cf539408-bba9-4e77-9f85-72019013db37')
                ),
                [
                ],
            ],
        ];
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
