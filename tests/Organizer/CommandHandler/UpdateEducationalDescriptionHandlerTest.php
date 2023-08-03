<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateEducationalDescriptionHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new UpdateEducationalDescriptionHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     * @dataProvider updateEducationalDescriptionProvider
     */
    public function it_handles_setting_a_description(
        array $given,
        UpdateEducationalDescription $updateEducationalDescription,
        array $then
    ): void {
        $this->scenario
            ->withAggregateId('5e360b25-fd85-4dac-acf4-0571e0b57dce')
            ->given($given)
            ->when($updateEducationalDescription)
            ->then($then);
    }

    public function updateEducationalDescriptionProvider(): array
    {
        return [
            'Set an initial educational description' => [
                [
                    $this->organizerCreated(),
                ],
                new UpdateEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Educatieve beschrijving NL'),
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
            ],
            'Update an educational description' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Updated educatieve beschrijving NL'),
                    new Language('nl')
                ),
                [
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Updated educatieve beschrijving NL',
                        'nl'
                    ),
                ],
            ],
            'Add a translated educational description' => [
                [
                    $this->organizerCreated(),
                    new DescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Educatieve beschrijving DE'),
                    new Language('de')
                ),
                [
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving DE',
                        'de'
                    ),
                ],
            ],
            'No update when educational description is the same' => [
                [
                    $this->organizerCreated(),
                    new EducationalDescriptionUpdated(
                        '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                        'Educatieve beschrijving NL',
                        'nl'
                    ),
                ],
                new UpdateEducationalDescription(
                    '5e360b25-fd85-4dac-acf4-0571e0b57dce',
                    new Description('Educatieve beschrijving NL'),
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
