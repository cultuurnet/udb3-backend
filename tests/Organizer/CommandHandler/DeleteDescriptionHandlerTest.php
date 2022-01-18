<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteDescription;
use CultuurNet\UDB3\Organizer\Events\DescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class DeleteDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new DeleteDescriptionHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     * @dataProvider deleteDescriptionProvider
     */
    public function it_handles_deleting_a_description(
        array $given,
        DeleteDescription $deleteDescription,
        array $then
    ): void {
        $this->scenario
            ->withAggregateId('5e360b25-fd85-4dac-acf4-0571e0b57dce')
            ->given($given)
            ->when($deleteDescription)
            ->then($then);
    }

    public function deleteDescriptionProvider(): array
    {
        return [
            'Delete an existing description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Description EN',
                        'en'
                    ),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new DescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Delete the last description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new DescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Try deleting a non-existing description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('fr')
                ),
                [
                ],
            ],
        ];
    }

    private function organizerCreated(): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            '5e360b25-fd85-4dac-acf4-0571e0b57dce',
            'en',
            'https://www.publiq.be',
            'publiq'
        );
    }
}
