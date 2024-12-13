<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Commands\RejectOwnership;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipRepository;

class RejectOwnershipHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new RejectOwnershipHandler(new OwnershipRepository($eventStore, $eventBus));
    }

    /**
     * @test
     */
    public function it_handles_approving_ownership(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
            ])
            ->when(
                new RejectOwnership(new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'))
            )
            ->then([
                new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ]);
    }
}
