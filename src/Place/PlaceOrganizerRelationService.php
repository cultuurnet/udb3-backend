<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

class PlaceOrganizerRelationService implements OrganizerRelationServiceInterface
{
    private CommandBus $commandBus;

    private PlaceRelationsRepository $relationsRepository;

    public function __construct(
        CommandBus $commandBus,
        PlaceRelationsRepository $relationsRepository
    ) {
        $this->commandBus = $commandBus;
        $this->relationsRepository = $relationsRepository;
    }

    public function deleteOrganizer(string $organizerId): void
    {
        $placeIds = $this->relationsRepository->getPlacesOrganizedByOrganizer($organizerId);

        foreach ($placeIds as $placeId) {
            $this->commandBus->dispatch(new DeleteOrganizer($placeId, $organizerId));
        }
    }
}
