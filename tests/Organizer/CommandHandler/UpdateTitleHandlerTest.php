<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Title as LegacyTitle;
use ValueObjects\Geography\Country;

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
            ->then([new TitleUpdated($id, new LegacyTitle('Nieuwe Titel'))]);
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
            new LegacyTitle('Organizer Title'),
            [
                new Address(
                    new Street('Kerkstraat 69'),
                    new PostalCode('9630'),
                    new Locality('Zottegem'),
                    Country::fromNative('BE')
                ),
            ],
            ['phone'],
            ['email'],
            ['url']
        );
    }
}
