<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteEducationalDescription;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class DeleteEducationalDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new DeleteEducationalDescriptionHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     * @dataProvider deleteEducationalDescriptionProvider
     */
    public function it_handles_deleting_a_description(
        array $given,
        DeleteEducationalDescription $deleteEducationalDescription,
        array $then
    ): void {
        $this->scenario
            ->withAggregateId('5e360b25-fd85-4dac-acf4-0571e0b57dce')
            ->given($given)
            ->when($deleteEducationalDescription)
            ->then($then);
    }

    public function deleteEducationalDescriptionProvider(): array
    {
        return [
            'Delete an existing educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educational description EN',
                        'en'
                    ),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Delete only educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve Beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Delete the first educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educational description EN',
                        'en'
                    ),
                ],
                new DeleteEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Delete the last educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionDeleted(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'nl'
                    ),
                ],
            ],
            'Try deleting a non-existing educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new DeleteEducationalDescription(
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
