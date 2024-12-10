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
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\AddImage;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class AddImageHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new AddImageHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_adding_an_image(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreatedWithUniqueWebsite($id)])
            ->when(new AddImage(
                $id,
                new Image(
                    new UUID('cf539408-bba9-4e77-9f85-72019013db37'),
                    new Language('nl'),
                    new Description('Description of the image'),
                    new CopyrightHolder('publiq')
                )
            ))
            ->then([
                new ImageAdded(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'nl',
                    'Description of the image',
                    'publiq'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_prevents_adding_the_same_image_twice(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                new ImageAdded(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'nl',
                    'Description of the image',
                    'publiq'
                ),
            ])
            ->when(new AddImage(
                $id,
                new Image(
                    new UUID('cf539408-bba9-4e77-9f85-72019013db37'),
                    new Language('nl'),
                    new Description('Description of the image'),
                    new CopyrightHolder('publiq')
                )
            ))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_multiple_images(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                $this->organizerCreatedWithUniqueWebsite($id),
                new ImageAdded(
                    $id,
                    'cf539408-bba9-4e77-9f85-72019013db37',
                    'nl',
                    'Description of the image',
                    'publiq'
                ),
            ])
            ->when(new AddImage(
                $id,
                new Image(
                    new UUID('0b02240d-5fc0-4efa-8a6f-4281f695dd5f'),
                    new Language('en'),
                    new Description('Another image'),
                    new CopyrightHolder('publiq')
                )
            ))
            ->then([
                new ImageAdded(
                    $id,
                    '0b02240d-5fc0-4efa-8a6f-4281f695dd5f',
                    'en',
                    'Another image',
                    'publiq'
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
