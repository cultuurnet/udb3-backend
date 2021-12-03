<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateTitleHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateTitleHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_updating_title_in_default_language_nl(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new UpdateTitle($id, new Title('Nieuwe Titel'), new Language('nl')))
            ->then([new TitleUpdated($id, 'Nieuwe Titel')]);
    }

    /**
     * @test
     */
    public function it_handles_updating_title_in_non_default_language(): void
    {
        $id = '5e360b25-fd85-4dac-acf4-0571e0b57dce';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->organizerCreated($id)])
            ->when(new UpdateTitle($id, new Title('New Title'), new Language('fr')))
            ->then([new TitleTranslated($id, 'New Title', 'fr')]);
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            'Organizer Title',
            'Kerkstraat 69',
            '9630',
            'Zottegem',
            'BE',
            ['phone'],
            ['email'],
            ['url'],
        );
    }
}
