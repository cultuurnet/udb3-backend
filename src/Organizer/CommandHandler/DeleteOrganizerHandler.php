<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class DeleteOrganizerHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;
    private OrganizerRelationServiceInterface $eventRelations;
    private OrganizerRelationServiceInterface $placeRelations;

    public function __construct(
        OrganizerRepository $organizerRepository,
        OrganizerRelationServiceInterface $eventRelations,
        OrganizerRelationServiceInterface $placeRelations
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

        $this->eventRelations->deleteOrganizer($command->getItemId());
        $this->placeRelations->deleteOrganizer($command->getItemId());

        $organizer = $this->organizerRepository->load($command->getItemId());
        $organizer->delete();
        $this->organizerRepository->save($organizer);
    }
}
