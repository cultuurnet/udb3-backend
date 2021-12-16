<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateDescriptionHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     * @dataProvider updateDescriptionProvider
     */
    public function it_handles_setting_a_description(
        array $given,
        UpdateDescription $updateDescription,
        array $then
    ): void {
        $this->scenario
            ->withAggregateId('5e360b25-fd85-4dac-acf4-0571e0b57dce')
            ->given($given)
            ->when($updateDescription)
            ->then($then);
    }

    public function updateDescriptionProvider(): array
    {
        return [
            'Set an initial description' => [
                [
                    $this->organizerCreated(),
                ],
                new UpdateDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Beschrijving NL'),
                    new Language('nl')
                ),
                [
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
            ],
            'Update a description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Updated beschrijving NL'),
                    new Language('nl')
                ),
                [
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Updated beschrijving NL',
                        'nl'
                    ),
                ],
            ],
            'Add a translated description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Beschrijving DE'),
                    new Language('de')
                ),
                [
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving DE',
                        'de'
                    ),
                ],
            ],
            'No update when description is the same' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Beschrijving NL'),
                    new Language('nl')
                ),
                [],
            ],
        ];
    }

    private function organizerCreated(): OrganizerCreated
    {
        return new OrganizerCreated(
            '5e360b25-fd85-4dac-acf4-0571e0b57dce',
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
