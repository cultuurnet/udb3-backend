<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Organizer\Commands\ConvertDescriptionToEducationalDescription;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class ConvertDescriptionToEducationalDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    private const ID = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new ConvertDescriptionToEducationalDescriptionHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_converts_a_description_to_educational_description(): void
    {
        $this->scenario
            ->withAggregateId(self::ID)
            ->given([
                $this->organizerCreated(),
                new DescriptionUpdated(self::ID, 'test', 'nl'),
                new DescriptionUpdated(self::ID, 'test', 'fr'),
            ])
            ->when(new ConvertDescriptionToEducationalDescription(self::ID))
            ->then([
                new EducationalDescriptionUpdated(self::ID, 'test', 'nl'),
                new EducationalDescriptionUpdated(self::ID, 'test', 'fr'),
            ]);
    }

    private function organizerCreated(): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            self::ID,
            'en',
            'https://www.publiq.be',
            'publiq'
        );
    }
}
