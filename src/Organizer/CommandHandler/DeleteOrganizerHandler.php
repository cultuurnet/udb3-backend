<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

final class DeleteOrganizerHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;
    private EventRelationsRepository $eventRelations;
    private PlaceRelationsRepository $placeRelations;

    public function __construct(
        OrganizerRepository $organizerRepository,
        EventRelationsRepository $eventRelations,
        PlaceRelationsRepository $placeRelations
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->eventRelations = $eventRelations;
        $this->placeRelations = $placeRelations;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteOrganizer) {
            return;
        }

        $eventIds = $this->eventRelations->getEventsOrganizedByOrganizer($command->getItemId());
        $placeIds = $this->placeRelations->getPlacesOrganizedByOrganizer($command->getItemId());

        foreach ($eventIds as $eventId) {
            // $this->commandBus->dispatch(new DeleteOrganizer($eventId, $command->getItemId()));
        }

        foreach ($placeIds as $placeId) {
            // $this->commandBus->dispatch(new DeleteOrganizer($placeId, $command->getItemId()));
        }

        $organizer = $this->organizerRepository->load($command->getItemId());
        $organizer->delete();
        $this->organizerRepository->save($organizer);
    }
}
