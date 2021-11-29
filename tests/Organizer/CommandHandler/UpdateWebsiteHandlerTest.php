<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

class UpdateWebsiteHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateWebsiteHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_the_website(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            $id,
            'nl',
            'https://www.madewithlove.be',
            'Organizer Title'
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$organizerCreated])
            ->when(new UpdateWebsite($id, new Url('https://www.publiq.be')))
            ->then([new WebsiteUpdated($id, 'https://www.publiq.be')]);
    }
}
