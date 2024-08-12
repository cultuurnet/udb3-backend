<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteOrganizerHandlerTest extends CommandHandlerScenarioTestCase
{
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        /** @var OrganizerRelationServiceInterface&MockObject $organizerRelationService */
        $organizerRelationService = $this->createMock(OrganizerRelationServiceInterface::class);

        $organizerRelationService->expects($this->exactly(2))
            ->method('deleteOrganizer')
            ->with('40021958-0ad8-46bd-8528-3ac3686818a1');

        return new DeleteOrganizerHandler(
            new OrganizerRepository($eventStore, $eventBus),
            $organizerRelationService,
            $organizerRelationService
        );
    }

    /**
     * @test
     */
    public function it_handles_delete_organizer(): void
    {
        $id = '40021958-0ad8-46bd-8528-3ac3686818a1';

        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            $id,
            'nl',
            'https://www.madewithlove.be',
            'Organizer Title'
        );

        $this->scenario
            ->withAggregateId($id)
            ->given([$organizerCreated])
            ->when(new DeleteOrganizer($id))
            ->then([new OrganizerDeleted($id)]);
    }
}
