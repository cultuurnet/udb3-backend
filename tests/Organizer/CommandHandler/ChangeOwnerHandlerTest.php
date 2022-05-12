<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Organizer\Commands\ChangeOwner;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class ChangeOwnerHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new ChangeOwnerHandler(new OrganizerRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_setting_a_new_owner(): void
    {
        $this->scenario
            ->withAggregateId('5c5a7abd-04ab-4575-8eab-befc2d8be064')
            ->given([
                $this->organizerCreatedWithUniqueWebsite(),
            ])
            ->when(
                new ChangeOwner(
                    '5c5a7abd-04ab-4575-8eab-befc2d8be064',
                    'f7ea40a7-4758-4798-a059-65740ab53bcb',
                ),
            )
            ->then([
                new OwnerChanged(
                    '5c5a7abd-04ab-4575-8eab-befc2d8be064',
                    'f7ea40a7-4758-4798-a059-65740ab53bcb'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_ignores_the_same_owner(): void
    {
        $this->scenario
            ->withAggregateId('5c5a7abd-04ab-4575-8eab-befc2d8be064')
            ->given([
                $this->organizerCreatedWithUniqueWebsite(),
                new OwnerChanged(
                    '5c5a7abd-04ab-4575-8eab-befc2d8be064',
                    'f7ea40a7-4758-4798-a059-65740ab53bcb'
                ),
            ])
            ->when(
                new ChangeOwner(
                    '5c5a7abd-04ab-4575-8eab-befc2d8be064',
                    'f7ea40a7-4758-4798-a059-65740ab53bcb',
                ),
            )
            ->then([]);
    }

    private function organizerCreatedWithUniqueWebsite(): OrganizerCreatedWithUniqueWebsite
    {
        return new OrganizerCreatedWithUniqueWebsite(
            '5c5a7abd-04ab-4575-8eab-befc2d8be064',
            'nl',
            'https://www.publiq.be',
            'publiq'
        );
    }
}
