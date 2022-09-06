<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;

class EventOrganizerRelationService implements OrganizerRelationServiceInterface
{
    private CommandBus $commandBus;

    private EventRelationsRepository $relationsRepository;

    public function __construct(
        CommandBus $commandBus,
        EventRelationsRepository $relationsRepository
    ) {
        $this->commandBus = $commandBus;
        $this->relationsRepository = $relationsRepository;
    }

    public function deleteOrganizer(string $organizerId): void
    {
        $eventIds = $this->relationsRepository->getEventsOrganizedByOrganizer($organizerId);

        foreach ($eventIds as $eventId) {
            $this->commandBus->dispatch(new DeleteOrganizer($eventId, $organizerId));
        }
    }
}
