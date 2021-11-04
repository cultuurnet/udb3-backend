<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url as LegacyUrl;

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
            new Language('nl'),
            LegacyUrl::fromNative('https://www.madewithlove.be'),
            new Title('Organizer Title')
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$organizerCreated])
            ->when(new UpdateWebsite($id, new Url('https://www.publiq.be')))
            ->then([new WebsiteUpdated($id, LegacyUrl::fromNative('https://www.publiq.be'))]);
    }
}
