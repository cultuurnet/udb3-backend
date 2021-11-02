<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class UpdateContactPointHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateContactPointHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_the_contact_point(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            $id,
            new Language('nl'),
            Url::fromNative('https://www.madewithlove.be'),
            new Title('Organizer Title')
        );

        $contactPoint = new ContactPoint(
            ['016 10 20 30'],
            ['info@pulbiq.be'],
            ['https://www.publiq.be']
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$organizerCreated])
            ->when(new UpdateContactPoint($id, $contactPoint))
            ->then([new ContactPointUpdated($id, $contactPoint)]);
    }
}
